<?php
include_once 'config_openfood.php';
session_start();
// valid_auth('member');  // Do not authenticate so this page is accessible to everyone


$content_faq = '
  <div id="faq_content" width="80%">

    <strong>Click on the question to see an answer:</strong>
    <ul>
      <li> <a href="#order1">How do I order online with this shopping cart?</a>
      <li> <a href="#order2">How do I order without using the online shopping cart?</a>
      <li> <a href="#order3">Can I change my order?</a>
      <li> <a href="#order4">When does ordering end for this cycle?</a>
      <li> <a href="#order5">How can I cancel my order?</a>
    </ul>
    <ul>
      <li> <a href="#pay1">How do I pay?</a>
    </ul>
    <ul>
      <li> <a href="#del1">When can I pick up or have my items delivered?</a>
      <li> <a href="#del2">Can I change my delivery method once I have chosen it?</a>
    </ul>
    <ul>
      <li> <a href="#web1">I am getting an error on a page, what do I do?</a>
      <li> <a href="#web2">I have a suggestion on how to make this website easier to use.</a>
    </ul>
    <ul>
      <li> <a href="#q1">What if I have questions that are not covered in this list?</a>
    </ul>

    <div id="order1"></div>
    <p><strong>Q: How do I order online with this shopping cart?</strong></p>
    <p><strong>A:</strong> You must be logged in to place an order. Get started by clicking &ldquo;Sign-up now&rdquo; or Login using the box in the top right corner of the page.</p>
    <p>Everything on this site happens according to an &ldquo;Ordering Cycle&rdquo;. The Ordering Cycle&rsquo;s opening times, closing times, and Delivery Days, are visible on the main Shopping Panel. <p>
    <p>If Ordering is open for shopping, you can open a basket by clicking &ldquo;add to basket&rdquo; on a product or by selecting a location from the &ldquo;Select Location&rdquo; function on the Shopping Panel.</p>
    <ol>
      <li><strong>Adding items:</strong> You can browse through the product lists and click &ldquo;Add to Basket&rdquo;. When you do this, the system adds one of the items you have selected to your cart. You can adjust the quantity in your basket by pressing the +/- buttons near the basket icon. If you need to add notes to the producer, such as "red, medium sized tomatoes" or "make this a small pig", click on View Your Cart, place the cursor in the box for notes for that product, type in the notes, and then press the &quot;Update Message&quot; button to the right of the entry form. <span class="warn">When you are done, there is no need to submit your order &ndash; whatever remains in your basket when the order closes will be considered your order.</li>
      <li><strong>Removing items:</strong> To remove a product from your basket, press the - (minus) button near the basket icon until the quantity is reduced to zero.</li>
      <li><strong>Viewing and editing your basket:</strong> You can edit your order up until the time that the ordering cycle closes. To edit your order (add or subtract items, change quantities, add notes), log in and navigate to the shopping panel, then click &ldquo;View Current Basket&rdquo; under &ldquo;Current Order Status&rdquo;. Again, there is no need to submit your order &ndash; whatever remains in your basket when the order closes will be considered your order.</li>
    </ol>
    <p><span class="warn">Note:</span> The shopping cart might be configured to show a subtotal but it will not necessarily subtotal everything you have ordered because items with random weights (such as packages of meat or cheese) will not be totaled until that information is updated by the producers.</p>

    <div id="order2"></div>
    <p><strong>Q: How do I order without using the online shopping cart?</strong></p>
    <p><strong>A:</strong> For ordering without using this online shopping cart, please email <a href="mailto:'.CUSTOMER_EMAIL.'">'.CUSTOMER_EMAIL.'</a> to ask if there is a &quot;computer buddy&quot; who can take your order by phone or fax.</p>

    <div id="order3"></div>
    <p><strong>Q: Can I change my order?</strong></p>
    <p><strong>A:</strong> You can log in and change your order until the order closes. The current cycle closes at: <strong>'.date ('g:i a, F j', strtotime (ActiveCycle::date_closed())).'</strong>. Between then and the delivery day, producers will be entering weights on any items that need it and putting your order together. You can view your temporary invoice in progress during that time by logging in.</p>

    <div id="order4"></div>
    <p><strong>Q: When does ordering end for this cycle?</strong></p>
    <p><strong>A:</strong> You can log in and change your order until <strong>'.date ('g:i a, F j', strtotime (ActiveCycle::date_closed())).'</strong>.</p>

    <div id="order5"></div>
    <p><strong>Q: How can I cancel my order?</strong></p>
    <p><strong>A:</strong> To cancel your order, you must change the quantity for each item in your shopping cart to zero.  Any items that remain in the shopping cart when the order closes will be considered a valid order and you will be expected to pay for them.</p>

    <div id="pay1"></div>
    <p><strong>Q: How do I pay?</strong></p>
    <p><strong>A:</strong> You will receive a paper copy of your invoice with your order on delivery day with the final total owed. Then you can pay by one of the accepted methods, which might include payment by check, PayPal, or some other means. You will also be able to view your final invoice online after delivery day. The mailing address is on the invoice. If paying by PayPal, please use the link at the bottom of the invoice.</p>

    <div id="del1"></div>
    <p><strong>Q: When can I pick up or have my items delivered?</strong></p>
    <p><strong>A:</strong> The standard delivery day is <strong>'.date ('F j', strtotime (ActiveCycle::delivery_date())).'</strong>, although this might vary for your particular site. Your temporary invoice (viewable after ordering is closed) will have the information on pick up time, location, or delivery. If you chose delivery, which is not available in all areas, a route manager will be in touch to coordinate delivery with you.</p>

    <div id="del2"></div>
    <p><strong>Q: Can I change my delivery method once I have chosen it?</strong></p>
    <p><strong>A:</strong> Yes, go to the shopping panel and click &ldquo;Change&rdquo; beside your current delivery method under &ldquo;Current Order Status&rdquo; or contact us at <a href="mailto:'.ORDER_EMAIL.'">'.ORDER_EMAIL.'</a>. Be sure to make changes prior to the time ordering closes.</p>

    <div id="web1"></div>
    <p><strong>Q: I am getting an error on a page, what do I do?</strong></p>
    <p><strong>A:</strong> Please copy and paste the text of the error into an email along with what page it is and send it to <a href="mailto:'.WEBMASTER_EMAIL.'">'.WEBMASTER_EMAIL.'</a>. Please also explain what happened before that error occurred. It is also helpful to know what operating system and version (Windows, Apple, Android, etc.) and browser (Firefox, Internet Explorer, etc.) you are using. Thank you for your help in keeping this website working smoothly.</p>

    <div id="web2"></div>
    <p><strong>Q: I have a suggestion on how to make this website easier to use.</strong></p>
    <p><strong>A:</strong> Please send your suggestions to <a href="mailto:'.WEBMASTER_EMAIL.'">'.WEBMASTER_EMAIL.'</a>. Thank you for your help in keeping this website working smoothly.</p>

    <div id="q1"></div>
    <p><strong>Q: What if I have questions that are not covered in this list?</strong></p>
    <p><strong>A:</strong> You can contact the appropriate person by looking on the <a href="contact.php">Contact Us</a> page.</p>
  </div>';

$page_specific_css = '
    .warn {
      color:#800;
      }
    #faq_content {
      width:80%;
      }';

$page_title_html = '<span class="title">Member Resources</span>';
$page_subtitle_html = '<span class="subtitle">How to Order FAQ</span>';
$page_title = 'Member Resources: How to Order FAQ';
$page_tab = 'member_panel';

include("template_header.php");
echo '
  <!-- CONTENT BEGINS HERE -->
  '.$content_faq.'
  <!-- CONTENT ENDS HERE -->';
include("template_footer.php");
