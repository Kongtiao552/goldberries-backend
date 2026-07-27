<?php

require_once('../api_bootstrap.inc.php');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
  die_json(405, 'Invalid request method');
}

$select = [];
$stamp_columns = [];

foreach (range(0, 10) as $stamp_id) {
  $stamp_columns[] = "stamp$stamp_id";
  $select[] = "COUNT(stamp_submission.id) FILTER (WHERE stamp_submission.stamp_id = $stamp_id) AS stamp$stamp_id";
}

$select_str = implode(', ', $select);

$query = "SELECT
    COALESCE(campaign.id, fg_campaign.id) AS campaign_id,
    COALESCE(campaign.name, fg_campaign.name) AS campaign_name,
    COALESCE(campaign.url, fg_campaign.url) AS campaign_url,
    COALESCE(campaign.date_added, fg_campaign.date_added) AS campaign_date_added,
    COALESCE(campaign.icon_url, fg_campaign.icon_url) AS campaign_icon_url,
    COALESCE(campaign.sort_major_name, fg_campaign.sort_major_name) AS campaign_sort_major_name,
    COALESCE(campaign.sort_major_labels, fg_campaign.sort_major_labels) AS campaign_sort_major_labels,              
    COALESCE(campaign.sort_major_colors, fg_campaign.sort_major_colors) AS campaign_sort_major_colors,
    COALESCE(campaign.sort_minor_name, fg_campaign.sort_minor_name) AS campaign_sort_minor_name,
    COALESCE(campaign.sort_minor_labels, fg_campaign.sort_minor_labels) AS campaign_sort_minor_labels,              
    COALESCE(campaign.sort_minor_colors, fg_campaign.sort_minor_colors) AS campaign_sort_minor_colors,
    COALESCE(campaign.author_gb_id, fg_campaign.author_gb_id) AS campaign_author_gb_id,
    COALESCE(campaign.author_gb_name, fg_campaign.author_gb_name) AS campaign_author_gb_name,
    COALESCE(campaign.note, fg_campaign.note) AS campaign_note,

    map.id AS map_id,
    map.campaign_id AS map_campaign_id,
    map.name AS map_name,
    map.url AS map_url,
    map.date_added AS map_date_added,
    map.is_archived AS map_is_archived,
    map.sort_major AS map_sort_major,
    map.sort_minor AS map_sort_minor,
    map.sort_order AS map_sort_order,
    map.author_gb_id AS map_author_gb_id,
    map.author_gb_name AS map_author_gb_name,
    map.note AS map_note,
    map.collectibles AS map_collectibles,
    map.golden_changes AS map_golden_changes,
    map.counts_for_id AS map_counts_for_id,
    map.is_progress AS map_is_progress,
    map.bin AS map_bin,

    for_map.id AS for_map_id,
    for_map.campaign_id AS for_map_campaign_id,
    for_map.name AS for_map_name,
    for_map.url AS for_map_url,
    for_map.date_added AS for_map_date_added,
    for_map.is_archived AS for_map_is_archived,
    for_map.sort_major AS for_map_sort_major,
    for_map.sort_minor AS for_map_sort_minor,
    for_map.sort_order AS for_map_sort_order,
    for_map.author_gb_id AS for_map_author_gb_id,
    for_map.author_gb_name AS for_map_author_gb_name,
    for_map.note AS for_map_note,
    for_map.collectibles AS for_map_collectibles,
    for_map.golden_changes AS for_map_golden_changes,
    for_map.counts_for_id AS for_map_counts_for_id,
    for_map.is_progress AS for_map_is_progress,
    for_map.bin AS for_map_bin,

    challenge.id AS challenge_id,
    challenge.campaign_id AS challenge_campaign_id,
    challenge.map_id AS challenge_map_id,
    challenge.objective_id AS challenge_objective_id,
    challenge.label AS challenge_label,
    challenge.description AS challenge_description,
    challenge.difficulty_id AS challenge_difficulty_id,
    challenge.date_created AS challenge_date_created,
    challenge.requires_fc AS challenge_requires_fc,
    challenge.has_fc AS challenge_has_fc,
    challenge.is_arbitrary AS challenge_is_arbitrary,
    challenge.sort AS challenge_sort,
    challenge.icon_url AS challenge_icon_url,
    challenge.is_rejected AS challenge_is_rejected,
    challenge.reject_note AS challenge_reject_note,
    challenge.likes AS challenge_likes,

    cd.id AS difficulty_id,
    cd.name AS difficulty_name,
    cd.subtier AS difficulty_subtier,
    cd.sort AS difficulty_sort,

    objective.id AS objective_id,
    objective.name AS objective_name,
    objective.description AS objective_description,
    objective.display_name_suffix AS objective_display_name_suffix,
    objective.is_arbitrary AS objective_is_arbitrary,
    objective.icon_url AS objective_icon_url,

    $select_str,
    COUNT(stamp_submission.id) AS total

  FROM stamp_submission
  JOIN submission ON stamp_submission.submission_id = submission.id
  JOIN challenge ON submission.challenge_id = challenge.id
  LEFT JOIN map  ON challenge.map_id = map.id
  LEFT JOIN map for_map ON map.counts_for_id = for_map.id
  LEFT JOIN campaign  ON map.campaign_id = campaign.id
  LEFT JOIN campaign fg_campaign ON challenge.campaign_id = fg_campaign.id
  JOIN difficulty cd ON challenge.difficulty_id = cd.id
  JOIN objective  ON challenge.objective_id = objective.id

  GROUP BY campaign.id, fg_campaign.id, map.id, for_map.id, challenge.id, cd.id, objective.id

  ORDER BY total DESC
";

$result = pg_query_params_or_die($DB, $query);

$response = [];

while ($row = pg_fetch_assoc($result)) {
  $row_data = [];

  $row_data['challenge'] = new Challenge();
  $row_data['challenge']->apply_db_data($row, 'challenge_');
  $row_data['challenge']->expand_foreign_keys($row, 5);

  $row_data['stamps'] = [];

  foreach ($stamp_columns as $stamp_column) {
    $row_data['stamps'][] = intval($row[$stamp_column]);
  }

  $row_data['total'] = intval($row['total']);

  $response[] = $row_data;
}

api_write($response);