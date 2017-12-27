<?php

// POPUP FOOTER //////////////////////////////////////////
if ($display_as_popup == true) // Do not distinguish Wordpress vs. non-Wordpress for popups
  {
    echo '
      </div>
    </div>
  </body>
</html>';
  }
// REGULAR PAGE FOOTER ///////////////////////////////////
else
  {
    if (WORDPRESS_ENABLED == true)
      {
        echo '
        </div><!-- .entry-content -->
      </article><!-- #post-## -->
    </main><!-- .site-main -->
  </div><!-- .content-area -->';
        require_once (FILE_PATH.WORDPRESS_PATH.'wp-load.php');
        // get_header();
        if (WORDPRESS_SHOW_SIDEBAR == true) get_sidebar();
        get_footer();
      }
    else // WORDPRESS_ENABLED == false
      {
    echo '
    </div><!-- #content -->
    </div><!-- #content -->'.
    $content_login.'
  </body>
</html>';
      }
  }
