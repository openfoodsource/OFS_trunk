<?php
include_once 'config_openfood.php';
session_start();
valid_auth('member');

// This is a dummy page. The content is brought in from motd.html
$content = '';

include("template_header.php");
echo '
  <!-- CONTENT BEGINS HERE -->
  '.$content.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");
