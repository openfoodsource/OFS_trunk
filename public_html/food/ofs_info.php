<?php
include_once 'config_openfood.php';
session_start();
valid_auth('member');

$info_content = '
  <h3>Open Food Source Software</h3>
    <p>
      This software is distributed as gratis/libre open source software (GLOSS).
      Gratis means it is free and does not cost money.
      Libre means it is free for anyone to inspect and modify in any way.
    </p>
    <dl>
      <dd>This version: 1.1.2</dd>
      <dd>Website: openfoodsource.org</dd>
      <dd>Email: openfood@openfoodsource.org</dd>
    </dl>
  <h3>System Requirements</h3>
    <p>
      This software is known to function with the following software versions.
      These values are (mostly) lifted from the current development environment, 
      so, in most cases, prior versions (particularly recent versions) will probably function properly
      (please notify us if you discover that these requirements can be downgraded).
      <dd>
        <dt>PHP</dt>
        <dd>version >= 5.3</dd>
        <dt>MySQL</dt>
        <dd>version >= 14.14 or MariaDB >= 10.1.23</dd>
        <dt>HTMLDoc</dt>
        <dd>version >= 1.8.27 (Optional for producing PDF output)</dd>
        <dt>Wordpress</dt>
        <dd>version >= 4.8 (Optional for integrating a front-end CMS)</dd>
      </dd>
    </p>
  <h3>Other resources included with this software</h3>
    <p>
      Various libraries and supporting software have been included in this application.
      Among them are the following, modified slightly in some cases.
      Many thanks are due to those who have made them available.
      <dd>
        <dt>PHPMailer &mdash; version 5.2.16</dt>
        <dd>Repository: https://github.com/PHPMailer/PHPMailer</dd>
        <dt>Inflect (singularizer/pluralizer) &mdash; version 2007-12-17</dt>
        <dd>Website/Source: http://kuwamoto.org/2007/12/17/improved-pluralizing-in-php-actionscript-and-ror/</dd>
        <dt>Securimage PHP Captcha &mdash; version 3.6.4</dt>
        <dd>Website: http://www.phpcaptcha.org/</dd>
        <dt>Bootstrap</dt>
        <dd>Website: https://getbootstrap.com/</dd>
        <dt>Summernote</dt>
        <dd>Website: https://summernote.org/</dd>
        <dt>JavaScript-Load-Image</dt>
        <dd>Repository: https://github.com/blueimp/JavaScript-Load-Image</dd>
        <dt>JavaScript-rangeslider</dt>
        <dd>Website: http://rangeslider.js.org/</dd>
        <dt>JavaScript-templates</dt>
        <dd>Repository: https://github.com/blueimp/JavaScript-Templates</dd>
        <dt>Vis</dt>
        <dd>Repository: https://github.com/almende/vis<br />
            Website: http://rangeslider.js.org/</dd>
        <dt>jQuery</dt>
        <dd>Website: http://jquery.com/</dd>
        <dt>jQuery-File-Upload</dt>
        <dd>Repository: https://github.com/blueimp/jQuery-File-Upload</dd>
        <dt>jQuery-simplemodal</dt>
        <dd>Repository: https://github.com/ericmmartin/simplemodal<br />
            Website: http://www.ericmmartin.com/projects/simplemodal/</dd>
        <dt>jQuery-ui-widget</dt>
        <dd>Website: https://jqueryui.com/</dd>
        <dt></dt>
        <dd></dd>
      </dd>
    </p>
    ';

$page_title_html = '<span class="title">Open Food Source Software</span>';
$page_subtitle_html = '<span class="subtitle">The software that runs this site</span>';
$page_title = 'Open Food Source Software';
$page_tab = 'admin_panel';

include("template_header.php");
echo '
  <!-- CONTENT BEGINS HERE -->
  '.$info_content.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");

