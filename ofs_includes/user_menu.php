<?php

if (session_id() == '' && headers_sent() == 0) session_start ();
// Check if the member is logged in
if (isset ($_SESSION['member_id']))
  {
    $content_user_menu = '
      <div id="user_menu">
        <img id="user_image" alt="user image" src="//www.gravatar.com/avatar/'.$_SESSION['gravatar_hash'].'?s=64&amp;d=mm&amp;r=PG" class="user_logo avatar avatar-64 photo" height="64" width="64">
        <ul id="user_actions">
          <form method="post" action="'.PATH.'index.php" id="logout">
            <li id="user_menu_identity"><a class="display-name" href="'.PATH.'member_form.php">'.$_SESSION['show_name'].'</a></li>
            <li id="user_menu_action_logout"><input type="hidden" name="action" value="logout" form="logout"></li>
            <li id="user_menu_logout" class="button"><input type="submit" form="logout" value="Log out"></li>'.
            ($_SESSION['basket_id'] ? '
            <li id="basket_info_link" class="button"><a href="'.PATH.'product_list.php?type=customer_basket&basket_id='.$_SESSION['basket_id'].'">Basket: '.$_SESSION['basket_quantity'].' '.Inflect::pluralize_if ($_SESSION['basket_quantity'], 'product').'</a></li>'
            : '').'
          </form>
        </ul>
      </div>
      ';
  }
else
  {
    $content_user_menu = '
      <div id="user_menu">
        <img id="user_image" alt="utility image" src="'.DIR_GRAPHICS.'gear.png" class="user_logo avatar avatar-64 photo" height="64" width="64" />
        <ul id="user_actions">
          <form method="post" action="'.PATH.'index.php" id="login">
            <input type="hidden" name="action" value="login" form="login">
            <li id="login_username"><input type="text" placeholder="Username" name="username" form="login"></li>
            <li id="login_password"><input type="password" placeholder="Password" name="password" form="login"></li>
            <li id="login_new_account" class="button"><input type="submit" form="login" value="Login"></li>
            <li id="forgot_password" class="button"><a href="'.PATH.'reset_password.php">Forgot?</a></li>
            <li id="login_sign_up" class="button"><a href="'.PATH.'member_form.php">Sign up!</a></li>
          </form>
        </ul>
      </div>';
  }

$page_specific_stylesheets['user_menu'] = array (
  'name'=>'user_menu',
  'src'=>BASE_URL.PATH.'css/openfood-user_menu.css',
  'dependencies'=>array('ofs_stylesheet'),
  'version'=>'2.1.1',
  'media'=>'all',
  );
