<?php
include_once 'config_openfood.php';
session_start();
// valid_auth('member');  // Do not authenticate so this page is accessible to everyone


$content_faq = '
  <table width="80%">
    <tr>
      <td align="left">'.$font.'

        <b>Click on the question to see an answer:</b>
        <ul>
          <li> <a href="#order1">How do I order ONLINE with this shopping cart?</a>
          <li> <a href="#order2">How do I order NOT using this shopping cart?</a>
          <li> <a href="#order3">Can I change my order?</a>
          <li> <a href="#order4">When does ordering end for this month?</a>
          <li> <a href="#order5">How can I cancel my order?</a>
          <br><br>
          <li> <a href="#pay1">How do I pay?</a>
          <li> <a href="#pay2">How do I change my payment method?</a>
          <br><br>
          <li> <a href="#del1">When can I pick up or have my items delivered?</a>
          <li> <a href="#del2">Can I change my delivery method once I have chosen it?</a>
          <br><br>
          <li> <a href="#prdcr1">I am a producer, where do I send my product updates?</a>
          <br><br>
          <li> <a href="#web1">I am getting an error on a page, what do I do?</a>
          <li> <a href="#web2">I have a suggestion on how to make this website easier to use.</a>
          <br><br>
          <li> <a href="member_form.php">How do I update my contact information?</a>
          <br><br>
          <li> <a href="#q1">What if I have questions that are not covered in this list?</a>
        </ul>

        <div id="order1"></div>
        <br>
        <b>Q: How do I order online with this shopping cart?</b>
        <br>
        <b>A:</b> The member log-in page is <a href="'.PATH.'">'.PATH.'</a>. If an order is open for shopping, you can open a basket by selecting a location from the &quot;Select Location&quot; function on the Shopping Panel. There are two methods of selecting products you want to buy.
        <ol>
        <li>You can browse through the product lists and click "Add to Shopping Cart". When you do this, the system adds one of the items you have selected to your cart. You can adjust the quantity in your basket by pressing the +/- buttons near the basket icon. If you need to add notes to the producer, such as "red, medium sized tomatoes" or "make this a small pig", click on View Your Cart, place the cursor in the box for notes for that product, type in the notes, and then press the &quot;Update Message&quot; button to the right of the entry form. When you are done, there is no need to submit your order - whatever remains in your basket when the order closes will be considered your order. <br>
        <br>

        <li>To remove a product from your shopping cart, press the - (minus) button near the basket icon until the quantity is reduced to zero.<br>
        <br>

        <li>You can edit your order up until the time that the Order Desk closes at the end of Delivery Day. The time of closing is announced at the beginning of Order Week. To edit your order (add or subtract items, change quantities, add notes), log in at <a href="'.PATH.'">'.PATH.'</a> . There is no need to submit your order - whatever remains in your basket when the order closes will be considered your order.
        </ol><br>
        <!-- Note: the shopping cart will show a subtotal, but it will not necessarily subtotal everything you have ordered, as items with random weights (such as packages of meat or cheese) will not be totaled until that information is updated from the producers. -->


        <div id="order2"></div>
        <br><br>

        <b>Q: How do I order NOT using this shopping cart?</b>
        <br>
        <b>A:</b> For ordering NOT using this online shopping cart,
        please email <a href="mailto:'.CUSTOMER_EMAIL.'">'.CUSTOMER_EMAIL.'</a> to ask if there is a &quot;computer buddy&quot; who can take your order by phone or fax.

        <div id="order3"></div>
        <br><br>

        <b>Q: Can I change my order?</b>
        <br>
        <b>A:</b> You can log in and change your order until <strong>'.date ('g:i a, F j', strtotime (ActiveCycle::date_closed())).'</strong>. Between then and the delivery day, producers will be entering weights on any items that need it and putting your order together. You can view your temporary invoice in progress during that time by logging in.

        <div id="order4"></div>
        <br><br>

        <b>Q: When does ordering end for this month?</b>
        <br>
        <b>A:</b> You can log in and change your order until <strong>'.date ('g:i a, F j', strtotime (ActiveCycle::date_closed())).'</strong>.

        <div id="order5"></div>
        <br><br>

        <b>Q: How can I cancel my order?</b>
        <br>
        <b>A:</b> To cancel your order, you must change the quantity for each item in your shopping cart to zero.  Any items that
        remain in the shopping cart when the order closes will be considered a valid order and you will be expected to pay for them.

        <div id="pay1"></div>
        <br><br>

        <b>Q: How do I pay?</b>
        <br>
        <b>A:</b> You will receive a paper copy of your invoice with your order on delivery day with the final total owed.
        Then you can write a check and send it to the address on the invoice, or log in and pay by PayPal online.
        You will also be able to view your finalized invoice online after delivery day. The mailing address is on the invoice.
        If paying by PayPal, email payment to <a href="mailto:'.PAYPAL_EMAIL.'">'.PAYPAL_EMAIL.'</a>

        <div id="pay2"></div>
        <br><br>

        <b>Q: How do I change my payment method?</b>
        <br>
        <b>A:</b> To change how you will pay, once your invoice is finalized after delivery day, you will be shown totals for different methods of payment. You can then decide to write a check, or log on and pay by PayPal online. You will also be able to change your method of payment at that time. If you have questions about this at that time, contact us at <a href="mailto:'.HELP_EMAIL.'">'.HELP_EMAIL.'</a>

        <div id="del1"></div>
        <br><br>

        <b>Q: When can I pick up or have my items delivered?</b>
        <br>
        <b>A:</b> Delivery Day is <strong>'.date ('F j', strtotime (ActiveCycle::delivery_date())).'</strong>. Your temporary invoice (viewable after ordering is closed) will have the information on pick up location or delivery. If you chose delivery, a route manager will be in touch to coordinate delivery with you.

        <div id="del2"></div>
        <br><br>

        <b>Q: Can I change my delivery method once I have chosen it?</b>
        <br>
        <b>A:</b> Contact us at <a href="mailto:'.ORDER_EMAIL.'">'.ORDER_EMAIL.'</a> to change your delivery method.'.

//         <div id="prdcr1"></div>
//         <br><br>
// 
//         <b>Q: I am a producer, where do I send my product updates?</b>
//         <br>
//         <b>A:</b> Send them to <a href="mailto:'.PRICELIST_EMAIL.'">'.PRICELIST_EMAIL.'</a>.
'
        <div id="web1"></div>
        <br><br>

        <b>Q: I am getting an error on a page, what do I do?</b>
        <br>
        <b>A:</b> Please copy and paste the text of the error into an email along with what page it is and send it to <a href="mailto:'.WEBMASTER_EMAIL.'">'.WEBMASTER_EMAIL.'</a>. Please also explain what happened before that error occurred. Thank you for your help in keeping this website working smoothly.

        <div id="web2"></div>
        <br><br>

        <b>Q: I have a suggestion on how to make this website easier to use.</b>
        <br>
        <b>A:</b> Please send your suggestions to <a href="mailto:'.WEBMASTER_EMAIL.'">'.WEBMASTER_EMAIL.'</a>. Thank you for your help in keeping this website working smoothly.

        <div id="q1"></div>
        <br><br>

        <b>Q: What if I have questions that are not covered in this list?</b>
        <br>
        <b>A:</b> You can contact the appropriate person by looking on the <a href="contact.php">Contact Us</a> page.
        <br><br>

      </td>
    </tr>
  </table>';

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