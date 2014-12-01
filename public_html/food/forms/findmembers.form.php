<?php
$content_members .= '
<div align="center">

<div style="border:.5px black solid;background-color:#CC0000;width:300px;"><b>Find Users</b></div>
<div style="border:.5px black solid;width:300px;text-align:left;"><form name="find_user" id="find_user" method="post" action="member_interface.php?action=displayUsers">
  <p>Please enter part of one of the following pieces of information to find a  member:</p>
  <dl style="margin:1em;"> 
    <dd>First name</<dd>
    <dd>Last name</<dd>
    <dd>Preferred name</<dd>
    <dd>Business name</<dd>
    <dd>Username</<dd>
    <dd>Member number</<dd>
    <dd>Email address</<dd>
  </dl>
  <p style="margin:1em;"> 
    <input id="load_target" name="query" type="text" id="query">
    <input type="submit" name="Submit" value="Search">
  </p>
  </form>
</div>
</div>';
