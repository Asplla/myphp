<?php
$json = array(
  'time' => time(),
  'date' => date('Y-m-d'),
  'tech' => 'wxhub api'
);
echo json_encode($json);
