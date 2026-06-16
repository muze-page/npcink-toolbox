<?php
/**
 * Verifies that Phase 1 local automation runtime replay fixtures fail closed.
 *
 * @package Npcink_Toolbox
 */

$root = dirname( __DIR__ );

require_once $root . '/modules/local-automation-runtime/src/Contract/Replay_Validator.php';

$fixture_path = $root . '/modules/local-automation-runtime/tests/fixtures/dry-run-replay.json';
$fixture      = json_decode( (string) file_get_contents( $fixture_path ), true );

if ( ! is_array( $fixture ) ) {
	fwrite( STDERR, "Local automation runtime negative replay base fixture is not valid JSON.\n" );
	exit( 1 );
}

$validator = new \Npcink\LocalAutomationRuntime\Contract\Replay_Validator();

$valid_result = $validator->validate( $fixture );
if ( true !== ( $valid_result['valid'] ?? false ) ) {
	fwrite( STDERR, 'Local automation runtime negative replay base fixture failed unexpectedly: ' . implode( ', ', $valid_result['errors'] ?? array() ) . "\n" );
	exit( 1 );
}

$cases = array(
	'scheduler_created_true' => array(
		'mutate' => static function ( array $replay ): array {
			$replay['acceptance']['scheduler_created'] = true;
			return $replay;
		},
		'error'  => 'scheduler_created_not_false',
	),
	'worker_created_true' => array(
		'mutate' => static function ( array $replay ): array {
			$replay['acceptance']['worker_created'] = true;
			return $replay;
		},
		'error'  => 'worker_created_not_false',
	),
	'direct_wordpress_write_true' => array(
		'mutate' => static function ( array $replay ): array {
			$replay['preview']['safety']['direct_wordpress_write'] = true;
			return $replay;
		},
		'error'  => 'preview.safety.direct_wordpress_write_forbidden_true',
	),
	'action_running_status' => array(
		'mutate' => static function ( array $replay ): array {
			$replay['job']['actions'][0]['status'] = 'running';
			return $replay;
		},
		'error'  => 'action_0_execution_status_forbidden',
	),
	'blocked_count_mismatch' => array(
		'mutate' => static function ( array $replay ): array {
			$replay['job']['eligibility_summary']['blocked_count'] = 2;
			return $replay;
		},
		'error'  => 'blocked_count_mismatch',
	),
	'schedule_window_enabled' => array(
		'mutate' => static function ( array $replay ): array {
			$replay['job']['limits']['schedule_window'] = 'nightly';
			return $replay;
		},
		'error'  => 'schedule_window_not_phase_1',
	),
	'lease_timeout_enabled' => array(
		'mutate' => static function ( array $replay ): array {
			$replay['job']['limits']['lease_timeout_seconds'] = 300;
			return $replay;
		},
		'error'  => 'lease_timeout_seconds_not_phase_1',
	),
);

foreach ( $cases as $name => $case ) {
	$mutated = $case['mutate']( $fixture );
	$result  = $validator->validate( $mutated );
	$errors  = $result['errors'] ?? array();

	if ( true === ( $result['valid'] ?? false ) ) {
		fwrite( STDERR, 'Negative replay case passed unexpectedly: ' . $name . "\n" );
		exit( 1 );
	}

	if ( ! in_array( $case['error'], $errors, true ) ) {
		fwrite( STDERR, 'Negative replay case missed expected error for ' . $name . ': ' . $case['error'] . '; got ' . implode( ', ', $errors ) . "\n" );
		exit( 1 );
	}
}

echo "Local automation runtime negative replay cases: ok\n";
