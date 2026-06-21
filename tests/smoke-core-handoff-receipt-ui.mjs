#!/usr/bin/env node
/**
 * Browser fixture smoke for Toolbox Core handoff receipt rendering.
 *
 * This loads the real admin JavaScript in a small DOM and mocks REST responses.
 * It verifies the visible success and failure receipt loop without creating
 * WordPress, Adapter, or Core records.
 */

import { createRequire } from 'node:module';
import { existsSync } from 'node:fs';
import { pathToFileURL } from 'node:url';
import { resolve } from 'node:path';

function pass(message) {
	console.log(`PASS: ${message}`);
}

function fail(message) {
	console.error(`FAIL: ${message}`);
	process.exit(1);
}

function assert(condition, message) {
	if (!condition) {
		fail(message);
	}
	pass(message);
}

async function loadPlaywright() {
	try {
		return await import('playwright');
	} catch (error) {
		const require = createRequire(import.meta.url);
		const paths = String(process.env.NODE_PATH || '').split(':').filter(Boolean);
		try {
			const resolved = require.resolve('playwright', { paths });
			const module = await import(pathToFileURL(resolved).href);
			return module.chromium ? module : module.default;
		} catch (fallbackError) {
			fail(`Playwright is not available. Install it or set NODE_PATH to the bundled runtime. ${fallbackError.message || error.message}`);
		}
	}
}

const { chromium } = await loadPlaywright();
const browserOptions = {
	headless: process.env.HEADLESS !== '0',
};
const chrome = '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';
if (process.env.BROWSER_EXECUTABLE) {
	browserOptions.executablePath = process.env.BROWSER_EXECUTABLE;
} else if (existsSync(chrome)) {
	browserOptions.executablePath = chrome;
}

const browser = await chromium.launch(browserOptions);
try {
	const page = await browser.newPage();
	await page.setContent(`
		<!doctype html>
		<html>
			<body>
				<div data-toolbox-site-knowledge>
					<form data-toolbox-endpoint="site-knowledge/sync">
						<input name="query" value="fixture" />
						<button type="submit">Run Site Knowledge</button>
						<div class="npcink-toolbox__result is-empty" hidden></div>
					</form>
				</div>
			</body>
		</html>
	`);
	await page.evaluate(() => {
		window.wp = { i18n: { __: (text) => String(text) } };
		window.NpcinkToolbox = {
			restUrl: 'https://fixture.local/wp-json/npcink-toolbox/v1',
			adapterRestUrl: 'https://fixture.local/wp-json/npcink-openclaw-adapter/v1',
			coreAdminUrl: 'https://fixture.local/wp-admin/admin.php?page=npcink-governance-core',
			nonce: 'fixture',
			labels: {
				running: 'Running...',
				error: 'Request failed.',
			},
		};
		window.__toolboxReceiptSmokeMode = 'success';
		window.__toolboxReceiptSmokeRequests = [];
		window.fetch = async (url) => {
			const requestUrl = String(url);
			window.__toolboxReceiptSmokeRequests.push(requestUrl);
			const jsonResponse = (status, body) => Promise.resolve({
				ok: status >= 200 && status < 300,
				status,
				json: () => Promise.resolve(body),
			});
			if (requestUrl.endsWith('/site-knowledge/sync')) {
				return jsonResponse(200, {
					artifact_type: 'site_knowledge_sync_request',
					status: 'queued',
					message: 'Fixture sync response.',
					handoff: {
						handoff_type: 'proposal_input',
						write_posture: 'suggestion_only',
						final_write_path: 'core_proposal_required',
						requires_local_approval: true,
						evidence_count: 1,
						proposal_input: {
							evidence_refs: [{ id: 'fixture-evidence', title: 'Fixture evidence' }],
						},
					},
				});
			}
			if (requestUrl.endsWith('/flows/site-knowledge-review-plan')) {
				return jsonResponse(200, {
					artifact_type: 'site_knowledge_review_plan',
					target_ability_id: 'npcink-abilities-toolkit/create-draft',
					write_actions: [],
				});
			}
			if (requestUrl.endsWith('/proposals/from-plan')) {
				if (window.__toolboxReceiptSmokeMode === 'failure') {
					return jsonResponse(422, {
						code: 'fixture_core_handoff_rejected',
						message: 'Adapter rejected fixture handoff.',
						operator_feedback: {
							status: 'blocked',
							severity: 'warning',
							can_retry_after_revision: true,
							next_steps: ['Review fixture evidence and retry.'],
						},
					});
				}
				return jsonResponse(200, {
					proposals: [{
						proposal_id: 'proposal-fixture-123',
						status: 'pending',
						ability_id: 'npcink-abilities-toolkit/create-draft',
					}],
				});
			}
			return jsonResponse(404, { message: 'Unexpected fixture URL: ' + requestUrl });
		};
	});

	await page.addScriptTag({ path: resolve('assets/admin.js') });
	await page.locator('form[data-toolbox-endpoint="site-knowledge/sync"]').evaluate((form) => {
		form.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));
	});
	await page.waitForSelector('[data-toolbox-site-knowledge-review-submit]', { timeout: 5000 });
	await page.getByRole('button', { name: 'Submit Core review proposal' }).click();
	await page.waitForSelector('text=proposal-fixture-123', { timeout: 5000 });
	const successText = await page.locator('.npcink-toolbox__result').innerText();
	assert(successText.includes('Core handoff receipt'), 'Success UI renders the Core handoff receipt heading.');
	assert(successText.includes('toolbox_core_handoff_receipt.v1'), 'Success UI renders the receipt contract version.');
	assert(successText.includes('proposal-fixture-123'), 'Success UI renders the Core proposal id.');
	assert(successText.includes('Open in Core review'), 'Success UI renders the Core review link.');
	assert(successText.includes('ephemeral_response_only'), 'Success UI keeps receipt storage local and ephemeral.');

	await page.evaluate(() => {
		window.__toolboxReceiptSmokeMode = 'failure';
	});
	await page.locator('form[data-toolbox-endpoint="site-knowledge/sync"]').evaluate((form) => {
		form.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));
	});
	await page.waitForSelector('[data-toolbox-site-knowledge-review-submit]', { timeout: 5000 });
	await page.getByRole('button', { name: 'Submit Core review proposal' }).click();
	await page.waitForSelector('text=Site Knowledge Core handoff failed', { timeout: 5000 });
	const failureText = await page.locator('.npcink-toolbox__result').innerText();
	assert(failureText.includes('Adapter rejected fixture handoff.'), 'Failure UI renders the Adapter/Core error message.');
	assert(failureText.includes('Handoff Failed'), 'Failure UI renders failed receipt status.');
	assert(failureText.includes('Review Adapter Core Error'), 'Failure UI renders the operator recovery action.');
	assert(failureText.includes('Review fixture evidence and retry.'), 'Failure UI renders operator feedback next steps.');
	assert(failureText.includes('Core handoff error payload'), 'Failure UI keeps the raw failure payload in details.');
} finally {
	await browser.close();
}

pass('Core handoff receipt UI fixture smoke completed.');
