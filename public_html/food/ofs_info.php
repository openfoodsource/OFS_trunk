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

        <dt>MySQL</dt>
        <dd>Version >= 14.14 or MariaDB >= 10.1.23</dd>

        <dt>PHP</dt>
        <dd>Version >= 5.3</dd>

        <dt>PHP - imagick (Required for image processing)</dt>
        <dd>Version >= 6.2.4</dd>

        <dt>HTMLDoc (Optional)</dt>
        <dd>Version >= 1.8.27 (Optional for producing PDF output)</dd>

        <dt>Wordpress (Optional)</dt>
        <dd>Version >= 4.8 (Optional for integrating a front-end CMS)</dd>
        <dd>Wordpress Child Theme: TwentySeventeen-OFS (modified to work with OFS)</dd>
        <dd>Wordpress Plugins: Must be compatible with jQuery >= 3.0.0</dd>

      </dd>
    </p>
  <h3>Other resources included with this software</h3>
    <p>
      Various libraries and supporting software have been included in this application.
      Among them are the following, modified slightly in some cases.
      Many thanks are due to those who have made them available.
      <dd>

        <dt>PHPMailer &mdash; version 5.2.16</dt>
        <dd>Repository: <a href="https://github.com/PHPMailer/PHPMailer" target="_blank">https://github.com/PHPMailer/PHPMailer</a></dd>

        <dt>Inflect (singularizer/pluralizer) &mdash; published 2007-12-17</dt>
        <dd>Website/Source: <a href="http://kuwamoto.org/2007/12/17/improved-pluralizing-in-php-actionscript-and-ror/" target="_blank">http://kuwamoto.org/2007/12/17/improved-pluralizing-in-php-actionscript-and-ror/</a></dd>

        <dt>Securimage PHP Captcha &mdash; version 3.6.4</dt>
        <dd>Website: <a href="http://www.phpcaptcha.org/" target="_blank">http://www.phpcaptcha.org/</a></dd>
        <dd>Repository: <a href="https://github.com/dapphp/securimage" target="_blank">https://github.com/dapphp/securimage</a></dd>

        <dt>Bootstrap &mdash; version 3.3.1</dt>
        <dd>Website: <a href="https://getbootstrap.com/" target="_blank">https://getbootstrap.com/</a></dd>
        <dd>Repository: <a href="https://github.com/twbs/bootstrap" target="_blank">https://github.com/twbs/bootstrap</a></dd>

        <dt>JavaScript-Load-Image &mdash; version 1.13.0</dt>
        <dd>Repository: <a href="https://github.com/blueimp/JavaScript-Load-Image" target="_blank">https://github.com/blueimp/JavaScript-Load-Image</a></dd>

        <dt>JavaScript-rangeslider &mdash; version 2.1.1</dt>
        <dd>Website: <a href="http://rangeslider.js.org/" target="_blank">http://rangeslider.js.org/</a></dd>
        <dd>Repository: <a href="https://github.com/andreruffert/rangeslider.js" target="_blank">https://github.com/andreruffert/rangeslider.js</a></dd>

        <dt>JavaScript-templates &mdash; version 2.4.1</dt>
        <dd>Website: <a href="https://blueimp.github.io/JavaScript-Templates/" target="_blank">https://blueimp.github.io/JavaScript-Templates/</a></dd>
        <dd>Repository: <a href="https://github.com/blueimp/JavaScript-Templates" target="_blank">https://github.com/blueimp/JavaScript-Templates</a></dd>

        <dt>jQuery &mdash; version 3.2.1</dt>
        <dd>Website: <a href="http://jquery.com/" target="_blank">http://jquery.com/</a></dd>
        <dd>Repository: <a href="https://github.com/jquery/jquery" target="_blank">https://github.com/jquery/jquery</a></dd>

        <dt>jQuery-migrate &mdash; version 3.0.0</dt>
        <dd>Website: <a href="https://jquery.com/upgrade-guide/3.0/" target="_blank">https://jquery.com/upgrade-guide/3.0/</a></dd>
        <dd>Repository: <a href="https://github.com/jquery/jquery-migrate" target="_blank">https://github.com/jquery/jquery-migrate</a></dd>

        <dt>jQuery-mousewheel &mdash; version 3.0.4</dt>
        <dd>Website: <a href="https://plugins.jquery.com/mousewheel/" target="_blank">https://plugins.jquery.com/mousewheel/</a></dd>
        <dd>Repository: <a href="https://github.com/jquery/jquery-mousewheel" target="_blank">https://github.com/jquery/jquery-mousewheel</a></dd>

        <dt>jQuery-fileupload &mdash; version 5.42.0</dt>
        <dd>Website: <a href="https://blueimp.github.io/jQuery-File-Upload/" target="_blank">https://blueimp.github.io/jQuery-File-Upload/</a></dd>
        <dd>Repository: <a href="https://github.com/blueimp/jQuery-File-Upload" target="_blank">https://github.com/blueimp/jQuery-File-Upload</a></dd>

        <dt>Vis &mdash; version 4.21.0</dt>
        <dd>Website: <a href="http://visjs.org/" target="_blank">http://visjs.org/</a></dd>
        <dd>Repository: https://github.com/almende/vis</dd>

        <dt>jQuery-simplemodal &mdash; version 1.4.6</dt>
        <dd>Website: <a href="http://www.ericmmartin.com/projects/simplemodal/" target="_blank">http://www.ericmmartin.com/projects/simplemodal/<a></dd>
        <dd>Repository: <a href="https://github.com/ericmmartin/simplemodal" target="_blank">https://github.com/ericmmartin/simplemodal</a></dd>

        <dt>jQuery-ui &mdash; version 1.12.1</dt>
        <dd>Website: <a href="https://jqueryui.com/" target="_blank">https://jqueryui.com/</a></dd>
        <dd>Repository: <a href="https://github.com/jquery/jquery-ui" target="_blank">https://github.com/jquery/jquery-ui</a></dd>


<!-- SOFTWARE NOT YET INTEGRATED INTO OPENFOOD BASE

        <dt>Summernote &mdash; version 0.8.8</dt>
        <dd>Website: <a href="https://summernote.org/" target="_blank">https://summernote.org/</a></dd>
        <dd>Repository: <a href="https://github.com/summernote/summernote" target="_blank">https://github.com/summernote/summernote</a></dd>

-- END OF SOFTWARE NOT YET INTEGRATED -->


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

