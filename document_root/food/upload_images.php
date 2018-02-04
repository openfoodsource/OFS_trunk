<?php
include_once 'config_openfood.php';
session_start();
valid_auth('producer,producer_admin');

$page_content = '
<div class="container">
    <h1>Upload Images</h1>
    <h2 class="lead">Select product images to upload</h2>
    <!-- The file upload form used as target for the file upload widget -->
    <form id="fileupload" action="'.PATH.'receive_image_uploads.php" method="POST" enctype="multipart/form-data">
        <!-- The fileupload-buttonbar contains buttons to add/delete files and start/cancel the upload -->
        <div class="row fileupload-buttonbar">
            <div class="col-lg-7">
                <!-- The fileinput-button span is used to style the file input field as button -->
                <span class="btn btn-success fileinput-button">
                    <i class="glyphicon glyphicon-plus"></i>
                    <span>Add files...</span>
                    <input type="file" name="files[]" multiple>
                </span>
                <button type="submit" class="btn btn-primary start">
                    <i class="glyphicon glyphicon-upload"></i>
                    <span>Start upload</span>
                </button>
                <button type="reset" class="btn btn-warning cancel">
                    <i class="glyphicon glyphicon-ban-circle"></i>
                    <span>Cancel upload</span>
                </button>
            </div>
            <!-- The global progress state -->
            <div class="col-lg-5 fileupload-progress fade">
                <!-- The global progress bar -->
                <div class="progress progress-striped active" role="progressbar" aria-valuemin="0" aria-valuemax="100">
                    <div class="progress-bar progress-bar-success" style="width:0%;"></div>
                </div>
                <!-- The extended global progress state -->
                <div class="progress-extended">&nbsp;</div>
            </div>
        </div>
        <!-- The table listing the files available for upload/download -->
        <table role="presentation" class="table table-striped"><tbody class="files"></tbody></table>
    </form>
</div>
<!-- The template to display files available for upload -->
<script id="template-upload" type="text/x-tmpl">
{% for (var i=0, file; file=o.files[i]; i++) { %}
  <tr class="template-upload fade">
    <td>
      <span class="preview"></span>
    </td>
    <td>
      <p class="name"><input type="hidden" name="file_name[]" value="{%=file.name%}">{%=file.name%}</p>
      <strong class="error text-danger"></strong>
      <div class="extra-field-title">
        <label>Title:</label>
        <input name="title[]" placeholder="optional">
      </div>
      <div class="extra-field-caption">
        <label>Caption:</label>
        <input name="caption[]" placeholder="optional">
      </div>
    </td>
    <td>
      {% if (!i && !o.options.autoUpload) { %}
        <button class="btn btn-primary start" disabled>
          <i class="glyphicon glyphicon-upload"></i>
          <span>Start</span>
        </button>
      {% } %}
      {% if (!i) { %}
        <button class="btn btn-warning cancel">
          <i class="glyphicon glyphicon-ban-circle"></i>
          <span>Cancel</span>
        </button>
      {% } %}
      <p class="size">Processing...</p>
      <div class="progress progress-striped active" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"><div class="progress-bar progress-bar-success" style="width:0%;"></div></div>
    </td>
  </tr>
{% } %}
</script>
<!-- The template to display files available for download -->
<script id="template-download" type="text/x-tmpl">
{% for (var i=0, file; file=o.files[i]; i++) { %}
    <tr class="template-download fade">
        <td>
            <span class="preview">
                {% if (file.thumbnailUrl) { %}
                    <a href="{%=file.url%}" title="{%=file.name%}" download="{%=file.name%}" data-gallery><img src="{%=file.thumbnailUrl%}"></a>
                {% } %}
            </span>
        </td>
        <td>
            <p class="name">
                {% if (file.url) { %}
                    <a href="{%=file.url%}" title="{%=file.name%}" download="{%=file.name%}" {%=file.thumbnailUrl?\'data-gallery\':\'\'%}>{%=file.name%}</a>
                {% } else { %}
                    <span>{%=file.name%}</span>
                {% } %}
            <p class="title">{%=file.title||\'\'%}</p>
            <p class="caption">{%=file.caption||\'\'%}</p>
            </p>
            {% if (file.error) { %}
                <div><span class="label label-danger">Error</span> {%=file.error%}</div>
            {% } %}
        </td>
        <td>
            {% if (file.deleteUrl) { %}
                <button class="btn btn-danger delete" data-type="{%=file.deleteType%}" data-url="{%=file.deleteUrl%}"{% if (file.deleteWithCredentials) { %} data-xhr-fields=\'{"withCredentials":true}\'{% } %}>
                    <i class="glyphicon glyphicon-trash"></i>
                    <span>Delete</span>
                </button>
            {% } else { %}
                <button class="btn btn-warning cancel">
                    <i class="glyphicon glyphicon-ban-circle"></i>
                    <span>Cancel</span>
                </button>
            {% } %}
            <br><span class="size">{%=o.formatFileSize(file.size)%}</span>
        </td>
    </tr>
{% } %}
</script>';

$page_specific_stylesheets['bootstrap'] = array (
  'name'=>'bootstrap',
  'src'=>BASE_URL.PATH.'css/bootstrap.css',
  'dependencies'=>array(),
  'version'=>'2.1.1',
  'media'=>'all',
  );
$page_specific_stylesheets['upload_images'] = array (
  'name'=>'upload_images',
  'src'=>BASE_URL.PATH.'css/openfood-upload_images.css',
  'dependencies'=>array('openfood'),
  'version'=>'2.1.1',
  'media'=>'all',
  );

$display_as_popup = true;

$page_specific_scripts['jquery'] = array (
  'name'=>'jquery',
  'src'=>BASE_URL.PATH.'js/jquery.js',
  'dependencies'=>array(),
  'version'=>'3.2.1',
  'location'=>false
  );
$page_specific_scripts['bootstrap'] = array (
  'name'=>'bootstrap',
  'src'=>BASE_URL.PATH.'js/bootstrap.js',
  'dependencies'=>array(),
  'version'=>'1.7.2',
  'location'=>false
  );
$page_specific_scripts['tmpl'] = array (
  'name'=>'tmpl',
  'src'=>BASE_URL.PATH.'js/tmpl.js',
  'dependencies'=>array(),
  'version'=>'2.4.1',
  'location'=>false
  );
$page_specific_scripts['load-image'] = array (
  'name'=>'load-image',
  'src'=>BASE_URL.PATH.'js/load-image.js',
  'dependencies'=>array(),
  'version'=>'1.13.0',
  'location'=>false
  );
$page_specific_scripts['load-image-meta'] = array (
  'name'=>'load-image-meta',
  'src'=>BASE_URL.PATH.'js/load-image-meta.js',
  'dependencies'=>array('load-image'),
  'version'=>'1.0.2',
  'location'=>false
  );
$page_specific_scripts['jquery-ui'] = array (
  'name'=>'jquery-ui',
  'src'=>BASE_URL.PATH.'js/jquery-ui.js',
  'dependencies'=>array('jquery'),
  'version'=>'1.12.1',
  'location'=>false
  );
$page_specific_scripts['jquery-fileupload'] = array (
  'name'=>'jquery-fileupload',
  'src'=>BASE_URL.PATH.'js/jquery-fileupload.js',
  'dependencies'=>array('jquery', 'load-image','jquery-ui'),
  'version'=>'5.42.0',
  'location'=>false
  );
$page_specific_scripts['jquery-fileupload-ui'] = array (
  'name'=>'jquery-fileupload-ui',
  'src'=>BASE_URL.PATH.'js/jquery-fileupload-ui.js',
  'dependencies'=>array('jquery-fileupload'),
  'version'=>'9.6.0',
  'location'=>false
  );
$page_specific_scripts['jquery-fileupload-process'] = array (
  'name'=>'jquery-fileupload-process',
  'src'=>BASE_URL.PATH.'js/jquery-fileupload-process.js',
  'dependencies'=>array('jquery-fileupload'),
  'version'=>'1.7.2',
  'location'=>false
  );
$page_specific_scripts['jquery-fileupload-image'] = array (
  'name'=>'jquery-fileupload-image',
  'src'=>BASE_URL.PATH.'js/jquery-fileupload-image.js',
  'dependencies'=>array('jquery-fileupload-process'),
  'version'=>'1.7.2',
  'location'=>false
  );
// Following two types are not needed for PICTURES but included for completeness
// $page_specific_scripts['jquery-fileupload-audio'] = array (
//   'name'=>'jquery-fileupload-audio',
//   'src'=>BASE_URL.PATH.'js/jquery-fileupload-audio.js',
//   'dependencies'=>array('jquery-fileupload-ui'),
//   'version'=>'1.0.3',
//   'location'=>false
//   );
// $page_specific_scripts['jquery-fileupload-video'] = array (
//   'name'=>'jquery-fileupload-video',
//   'src'=>BASE_URL.PATH.'js/jquery-fileupload-video.js',
//   'dependencies'=>array('jquery-fileupload-ui'),
//   'version'=>'1.0.3',
//   'location'=>false
//   );
$page_specific_scripts['jquery-fileupload-validate'] = array (
  'name'=>'jquery-fileupload-validate',
  'src'=>BASE_URL.PATH.'js/jquery-fileupload-validate.js',
  'dependencies'=>array('jquery-fileupload-process'),
  'version'=>'1.7.2',
  'location'=>false
  );
$page_specific_scripts['jquery-fileupload-main'] = array (
  'name'=>'jquery-fileupload-main',
  'src'=>BASE_URL.PATH.'js/jquery-fileupload-main.js',
  'dependencies'=>array('jquery-fileupload'),
  'version'=>'1.7.2',
  'location'=>false
  );

$page_specific_javascript = '
  $(document).ready(function() {
    $("#fileupload").fileupload({
      url: "receive_image_uploads.php"
      }).on("fileuploadsubmit", function (e, data) {
        data.formData = data.context.find(":input").serializeArray();
      }); // End of #fileupload
    });';

include("template_header.php");
echo '
  <!-- CONTENT BEGINS HERE -->
  '.$page_content.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");
