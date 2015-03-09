<?php
include_once 'config_openfood.php';
session_start();
valid_auth('producer,producer_admin');

/*
 * jQuery File Upload Plugin Demo 9.1.0
 * https://github.com/blueimp/jQuery-File-Upload
 *
 * Copyright 2010, Sebastian Tschan
 * https://blueimp.net
 *
 * Licensed under the MIT license:
 * http://www.opensource.org/licenses/MIT
 *
 * Modified for OFS by ROYG 2014-11-22
 */

$page_content = '
<!DOCTYPE HTML>
<html lang="en">
<head>
<!-- Force latest IE rendering engine or ChromeFrame if installed -->
<!--[if IE]>
<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
<![endif]-->
<meta charset="utf-8">
<title>Upload Images</title>
<meta name="description" content="File Upload widget with multiple file selection, drag&amp;drop support, progress bars, validation and preview images, audio and video for jQuery. Supports cross-domain, chunked and resumable file uploads and client-side image resizing. Works with any server-side platform (PHP, Python, Ruby on Rails, Java, Node.js, Go etc.) that supports standard HTML form file uploads.">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<!-- Bootstrap styles -->
<link rel="stylesheet" href="'.PATH.'css/bootstrap.min.css">
<link rel="stylesheet" href="upload_images.css">
<!-- CSS adjustments for browsers with JavaScript disabled -->
<noscript><link rel="stylesheet" href="css/jquery.fileupload-noscript.css"></noscript>
<noscript><link rel="stylesheet" href="css/jquery.fileupload-ui-noscript.css"></noscript>
</head>
<body>
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
</script>
</body> 
<script src="'.PATH.'ajax/jquery.js"></script>
<script type="text/javascript">
jQuery.cachedScript = function( url, options ) {
  // Allow user to set any option except for dataType, cache, and url
  options = jQuery.extend( options || {}, {
    dataType: "script",
    cache: true,
    url: url
    });
  // Use jQuery.ajax() since it is more flexible than jQuery.getScript
  // Return the jqXHR object so we can chain callbacks
  return jQuery.ajax( options );
  };
// Ensure dependencies are loaded in order (this may be overkill, but it works)...
jQuery.cachedScript( "'.PATH.'js/tmpl.js" ).done(function() {
  });
jQuery.cachedScript( "'.PATH.'ajax/load-image.js" ).done(function() {
  jQuery.cachedScript( "'.PATH.'ajax/load-image-meta.js" ).done(function() {
    jQuery.cachedScript( "'.PATH.'ajax/jquery-ui.js" ).done(function() {
      jQuery.cachedScript( "'.PATH.'ajax/jquery-ui-widget.js" ).done(function() {
        jQuery.cachedScript( "'.PATH.'js/jquery.fileupload.js" ).done(function() {
          jQuery.cachedScript( "'.PATH.'js/jquery.fileupload-ui.js" ).done(function() {
          //jQuery.cachedScript( "'.PATH.'js/jquery.fileupload-audio.js" ).done(function() {
          //  });
          //jQuery.cachedScript( "'.PATH.'js/jquery.fileupload-video.js" ).done(function() {
          //  });
            jQuery.cachedScript( "'.PATH.'js/jquery.fileupload-image.js" ).done(function() {
              });
            });
          jQuery.cachedScript( "'.PATH.'js/jquery.fileupload-process.js" ).done(function() {
            jQuery.cachedScript( "'.PATH.'js/jquery.fileupload-validate.js" ).done(function() {
              jQuery.cachedScript( "'.PATH.'js/jquery.fileupload-main.js" ).done(function() {
                jQuery("#fileupload").fileupload({
                  url: "receive_image_uploads.php"
                  }).on("fileuploadsubmit", function (e, data) {
                    data.formData = data.context.find(":input").serializeArray();
                  }); // End of #fileupload
                });
              });
            });
          });
        });
      });
    });
  });
jQuery.cachedScript( "'.PATH.'js/bootstrap.min.js" ).done(function() {
  });
</script>
</html>';
echo $page_content;

