<?php

require_once('../api_bootstrap.inc.php');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
  die_json(405, 'Invalid request method');
}

$stamp_ids = range(0, 9);
$stamp_data = [];
$challenge_totals = [];
$challenge_ids = [];

$query = "SELECT ss.stamp_id, s.challenge_id
  FROM stamp_submission ss
  JOIN submission s ON s.id = ss.submission_id";
$result = pg_query_params_or_die($DB, $query);

while ($row = pg_fetch_assoc($result)) {
  $challenge_id = intval($row['challenge_id']);
  $stamp_id = intval($row['stamp_id']);

  if (!isset($stamp_data[$challenge_id])) {
    $stamp_data[$challenge_id] = array_fill(0, count($stamp_ids), 0);
    $challenge_totals[$challenge_id] = 0;
    $challenge_ids[$challenge_id] = true;
  }

  $challenge_totals[$challenge_id]++;

  if (in_array($stamp_id, $stamp_ids, true)) {
    $stamp_data[$challenge_id][$stamp_id]++;
  }
}

$response = [];

if (count($challenge_ids) > 0) {
  $challenge_id_list = "{" . implode(',', array_keys($challenge_ids)) . "}";
  $query = "SELECT * FROM view_challenges WHERE challenge_id = ANY ($1)";
  $result = pg_query_params_or_die($DB, $query, [$challenge_id_list]);

  while ($row = pg_fetch_assoc($result)) {
    $challenge_id = intval($row['challenge_id']);

    $row_data = [];
    $row_data['challenge'] = new Challenge();
    $row_data['challenge']->apply_db_data($row, 'challenge_');
    $row_data['challenge']->expand_foreign_keys($row, 5);
    $row_data['stamps'] = $stamp_data[$challenge_id];
    $row_data['total'] = $challenge_totals[$challenge_id];

    $response[] = $row_data;
  }
}

usort($response, function ($left, $right) {
  return $right['total'] <=> $left['total'];
});

api_write($response);