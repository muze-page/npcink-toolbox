(function () {
	'use strict';

	const config = window.MagickAIToolbox || {};

	function serialize(form) {
		const data = {};
		new FormData(form).forEach((value, key) => {
			data[key] = value;
		});
		return data;
	}

	function setResult(form, value) {
		const result = form.querySelector('.magick-ai-toolbox__result');
		if (!result) {
			return;
		}

		if (typeof value === 'string') {
			result.textContent = value;
			return;
		}

		result.textContent = JSON.stringify(value, null, 2);
	}

	async function runTool(form) {
		const endpoint = form.getAttribute('data-toolbox-endpoint');
		if (!endpoint || !config.restUrl) {
			return;
		}

		setResult(form, config.labels && config.labels.running ? config.labels.running : 'Running...');

		const response = await fetch(config.restUrl.replace(/\/$/, '') + '/' + endpoint, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': config.nonce || '',
			},
			body: JSON.stringify(serialize(form)),
		});

		const payload = await response.json();
		if (!response.ok) {
			throw payload;
		}

		if (payload && payload.text) {
			let text = payload.text;
			if (payload.annotations && payload.annotations.length) {
				text += '\n\nAnnotations:\n' + JSON.stringify(payload.annotations, null, 2);
			}
			setResult(form, text);
			return;
		}

		setResult(form, payload);
	}

	document.addEventListener('submit', function (event) {
		const form = event.target;
		if (!(form instanceof HTMLFormElement) || !form.hasAttribute('data-toolbox-endpoint')) {
			return;
		}

		event.preventDefault();
		runTool(form).catch((error) => {
			setResult(form, error && error.message ? error.message : (config.labels && config.labels.error ? config.labels.error : 'Request failed.'));
		});
	});
}());
