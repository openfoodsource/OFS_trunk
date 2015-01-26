<?php

if ($display_as_popup != true)
  {
    $content_footer = '
    </div><!-- #content -->'.
    $content_login.'
  </body>
</html>';
  }
else
  {
    $content_footer = '
  </body>
</html>';
  }

echo $content_footer;
