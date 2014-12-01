<?php
include_once 'config_openfood.php';
session_start();
valid_auth('producer_admin,producer');
?>

<html>
<head>
<title>Editing Product Information</title>
<style>
h3 {
  margin-top:1.4em;
  color:#262;
  }
h4 {
  display:inline;
  margin-right:0.5em;
  color:#642;
  }
strong {
  color:#800;
  }
</style>
</head>
<body bgcolor="#FFFFFF">
<font face="arial" size="-1">

<!-- CONTENT BEGINS HERE -->

<h2>Admin Controls</h2>

<div id="account_number"></div>
<h3>Account</h3>
<p>Normally this option would only be used for organization-sponsored products such as memberships or fund-raisers. This
selection allows funds to be sent directly into a particular account instead of accruing to the general producer
account for the entity.</p>

<div id="retail_staple"></div>
<h3>Food Stamp Retail and Staple Definitions</h3>
<p><h4>Retail Food:</h4>No fabric arts, no health and beauty, no soaps, no crafts, no firewood, no charitable
donations or CD sales, no household supplies or laundry detergent, nothing in the "Non-Food Item" category, no
pet supplies. No live plants or worm castings. It would however include prepared foods since they are cold and
packaged for take out.</p>
<p><h4>Retail Staple Food:</h4>Retail foods covered above but without prepared or hot foods, candy, condiments,
spices, coffee, tea, cocoa, carbonated or uncarbonated drinks. Bread probably qualifies for a staple food sale, but
probably not cake or cookies.</p>

<div id="sticky"></div>
<h3>Sticky</h3>
<p>This checkbox is used to indicate that a product, once added to a customer's basket, can not be changed by anyone
except an administrator. It is intended to be used for such things as membership dues that could cause problems if
they could be removed by members.</p>

<div id="hide_from_invoice"></div>
<h3>Hide From Invoice</h3>
<p>If checked, the item will not be listed as a line-item on invoices. Obviously, this will confuse people if the item
has a charge. This feature was originally used to allow members to vote by placing selections into their baskets. It
may have other uses, but should be considered carefully.</p>

<div id="product_fee_percent"></div>
<h3>Price Adjustment Fees</h3>
<p><h4>Product Markup:</h4>This is the only one of the three fields that is active on the edit-product-screen.
It allows specific products to be adjusted up or down in cost, independently of other products.</p>
<p><h4>Producer Markup:</h4>This (disabled) field shows the markup percentage for this particular producer. The
producer markup can be changed in the Category and Subcategories editor.</p>
<p><h4>Subcategory Markup:</h4>This (disabled) field shows the markup percentage for this particular subcategory.
the subcategory markup can be changed in the Category and Subcategories editor.</p>

<div id="confirmed"></div>
<h3>Confirm</h3>
<p>If checked, the product will be confirmed for listing to customers. Only a single version of a product may
be confirmed at any time, so checking this box will un-confirm all other versions of this product.</p>

<h2>Producer Controls</h2>

<div id="listing_auth_type"></div>
<h3>Listing Type</h3>
<p><h4>Retail to Members:</h4>Listing products here allows regular members to shop for them. Wholesale
 members will also be able to purchase these products, but they will benefit from any wholesale rate that has
 been set.</p>
<p><h4>Wholesale to Institutions:</h4>List products here if you would like to restrict sales to wholesale
buyers only. Regular members will not be able to see these products.</p>
<p><h4>Unlisted:</h4>Listing here will keep products from being listed. This might be used at the end
of the season or when unable to fill additional orders for some reason.</p>
<p><h4>Archived:</h4>List products here if you do not wish to delete them but would like to keep them off
your Unlisted set. It is functionally the same as Unlisted.</p>

<div id="tangible"></div>
<h3>Tangible</h3>
<p>This checkbox is intended to identify products that are tangible in nature. In other words, products that need some
kind of handling to get them to the customer, as most products will. This should be UN-checked for products that do
not require any sort of handling or transport (or maybe for products that will be direct-mailed). The reason for this
field is to handle situations like exempting delivery charges for products that do not need to be delivered.</p>

<div id="product_name"></div>
<h3>Product Name</h3>
<p>A brief descriptive name. There should be some consistency in the product number/name so that changes to a product
should be fairly small things such as change of price, category listing, descriptive information.  But if it is
really very different you should probably add it as a new product  This should be better for your sales in the
long run.  Similarly, if you have an existing unlisted product, it should improve your sales to keep the product
number rather than create a new product.</p>

<div id="product_description"></div>
<h3>Product Description</h3>
<p>Though not required, this is a place to promote a product more than just listing the name. It is also a place to include ingredients or
other special process information, if required. If the approximate size, weight or contents are not clear from the
name of the product, list those details here. If it is a package of several items, the approximate (or exact,
whichever the case may be) number of items in the package should be listed.</p>

<div id="subcategory_id"></div>
<h3>Subcategory</h3>
<p>Please choose the best category and subcategory that the product should be listed under. If we do not have an
existing category and/or subcategory, please give us some guidance on how the product should be categorized.
To suggest an additional category, please send an email to
<a href="mailto:<?php echo PRICELIST_EMAIL; ?>"><?php echo PRICELIST_EMAIL; ?></a>.</p>

<div id="inventory_pull"></div><div id="inventory_id"></div>
<h3>Inventory</h3>
<p>Inventory can be combined for multiple different products. The default option &ldquo;DO NOT USE ANY INVENTORY&rdquo; is
normally selected. If you would like to control this product with inventory, you can either select an existing
inventory unit or select &ldquo;CREATE A NEW INVENTORY UNIT&rdquo;. The latter will create a new inventory unit with
the same name as the product (the inventory name can be changed later without affecting the product name). The
&ldquo;decrease by...units&rdquo; is used to show how many of your inventory will be used by each purchase of this
product. <strong>NOTE:</strong> The actual inventory quantity is not set in this location &ndash; use the inventory page for that.</p>
<p><h4>Example using inventory across multiple products:</h4>Set your inventory to use the smallest unit of a
product you are likely to handle. Eggs make a good example. You could set "egg" as your inventory unit and you sell
three different egg products: half-dozen, dozen, and 18-pack. Then, for the half-dozen egg product, you would set
&ldquo;decrease by...units&rdquo; to six (6); for the dozen egg product you would set &ldquo;decrease by...units&rdquo;
to twelve (12); for the 18-pack egg product you would set &ldquo;decrease by...units&rdquo; to eighteen (18). Then, in
all cases, you will maintain your inventory at the number of actual eggs (not dozens) that you have available. And
when a customer buys a dozen eggs, your inventory will decrease by twelve (12) eggs.</p>

<div id="unit_price"></div><div id="pricing_unit"></div><div id="ordering_unit"></div>
<h3>Price, Pricing Unit, and Ordering Unit</h3>
<p>Enter your product price as the <strong>Coop Price</strong>. The producer price and customer price will be automatically
calculated from that. Producers will receive the producer price and customers will pay the customer price.</p>
<p>The price, the pricing unit (e.g. whatever comes after the &ldquo;per&rdquo; in $ per ____), and the ordering unit
(when the customer orders, they will order number of ______).  Also, we need to know if the item has a random weight
&ndash; the customer will not know the price until you provide a weight for it  after the item is ordered.</p>
</p>
<p><h4>Example 1:</h4>You are selling a 5 pound bag of wheat for $10.00. The price is $10.00. You should include
mention of the &ldquo;5 pound&rdquo; size in your product name or description (see note below). The pricing
unit is &ldquo;bag&rdquo; (because you are only selling whole bags).  The ordering unit is also &ldquo;bag&rdquo;
because the customer orders by the number of 5 pound bags that they wish to buy. This item is not considered random
weight because the bags always weigh the same and the customer knows the final price when it is ordered.  Notice in
this example that even though the flour ends up costing $2 per pound, you would not list as $2.00 per pound because
you are only selling 5 pound bags that cost $10.00. <strong>NOTE:</strong> Althought you could list this product with an ordering or
pricing unit of &ldquo;5 pound bag&rdquo;, it is best to avoid numbers at the beginning of ordering and pricing units.
Otherwise confusion can result when someone orders two &ldquo;5 pound bags&rdquo; which might be displayed as &ldquo;2
5 pound bags&rdquo; in some places.</p>
</p>
<p><h4>Example 2:</h4>You are selling a bag of ground beef.  The bag weights range between .75 and 1.25 pounds
and you sell the meat at $4.00 per pound. The price depends on the weight but you want the customer to order some
number of bags, not the number of pounds because you do not package it in exactly 1 pound bags. In this case, your
price would be $4.00, your pricing unit would be &ldquo;pound&rdquo;, and your ordering unit would be &ldquo;bag&rdquo;.
This is a random weight product because the price cannot be pre-determined by the customer. It can only be determined
after you (the producer) enter the actual weight.</p>
</p>
<p><h4>Example 3:</h4>You are selling packages of chicken breasts and the package varies in weight from a
little under 2 pounds to a little over 2 pounds.  However, you always charge the same price per package ($6.00).
In this case, price is $6.00, the pricing unit is &ldquo;package&rdquo;, and the ordering unit is package. This is
not a random weight product because the customer knows the price in advance.</p>
</p>
<p><h4>Example 4:</h4>You are selling tomatoes at $3.00 per pound. The customer can order by the pound. If the
customer orders 3 pounds, you have decided that you will always provide a minimum of 3 pounds but will not charge for
exact weight but instead charge for the weight ordered. So if the customer orders 3 pounds and you end up giving them
3.1 pounds, you still only charge $9.00. In this case, the price is $3.00. The pricing unit is &ldquo;pound&rdquo;
and the ordering unit is &ldquo;pound&rdquo;. This is not a random weight product because the customer can determine
what the price will be in advance. Modifying this example slightly, if you did decide that you want to charge for
exact weight (e.g. charge $9.30 for the 3.1 pound bag) then all of the other information would be the same, but now
this would be a random weight product because when the customer orders 3 pounds, he/she has no way of determining
the final price which depends upon your weighing the item).</p>
</p>

<p>In general, you should use short descriptive terms for the ordering and pricing units and keep details in the name or
product description. Some standard terms will be &ldquo;pound&rdquo;, &ldquo;bag&rdquo;, &ldquo;package&rdquo; but in
many cases it will be worthwhile to be even more descriptive. For instance, if you are selling T-bone steaks 1 to a
package at $8/pound, then instead of package you could put steak as the ordering unit. In this case the pricing unit
would still be pound. However, if the package had two steaks, you would probably use &ldquo;package&rdquo; as the
ordering unit instead of &ldquo;ackage of 2 steaks&rdquo; and put that information into the product name, such as
&ldquo;T-bone steaks, package of two&rdquo;. Any product that the customer orders by the item can also get descriptive
pricing and ordering units. For instance, if you are selling by the individual tomato, ear of corn, squash, jar or
jelly, etc. then you could list &ldquo;tomato&rdquo;, &ldquo;ear&rdquo;, &ldquo;squash&rdquo;, or &ldquo;jar&rdquo; as
the ordering unit. The pricing units could also be listed as &ldquo;tomato&rdquo;, &ldquo;ear&rdquo;,
&ldquo;squash&rdquo; or &ldquo;jar&rdquo;, or you could just use the generic &ldquo;each&rdquo; in the pricing unit.
It may be helpful when you choose these units to think of the way this information will appear on your product listing
and on invoices. Your ordering unit will be displayed on your product/price list as follows &ldquo;Order number of
___________.&rdquo; So if you choose &ldquo;steak&rdquo; as your pricing unit, your listing will say &ldquo;Order
number of steaks&rdquo;. On the customer invoice, the ordering unit will show up under the quantity heading with the #
ordered and the ordering unit (e.g., 1 steak, or 2 steaks). For pricing unit, the unit you choose will show up on the
product list and on the invoice as price/pricing unit. So for the T-bone above this would be $8/pound because pound
was the pricing unit. <strong>NOTE:</strong> The software will pluaralize your pricing and ordering units for you, so it is preferable
to use the singular form like &ldquo;package&rdquo; instead of &ldquo;packages&rdquo;.</p>

<div id="extra_charge"></div>
<h3>Extra Charge</h3>
<p>The extra charge option allows bypassing all other fees and taxes. This is mostly useful for items that have some kind
of deposit that will be refunded to the customer at a later date. Extra charge will be applied to each &ldquo;ordering
unit&rdquo; of a product that is purchased. For example, a turkey producer might take reservations for Thanksgiving
turkeys in July and charge $0.00 per turkey and $10.00 extra charge for the reservation. Then, when the turkey is grown
and ready for delivery, the turkey is sold for $2.50/lb and (minus) -$10.00 extra charge to refund the original
deposit. This way there are no fees or taxes paid on the deposit part of the transaction.</p>

<div id="random_weight"></div>
<h3>Random Weight</h3>
<p>If it is a random weight product (the price depends on the weight), we need to know the approximate range of weights.
Example: roast, sold by a package of one roast, price is $4.00/lb, the roasts weigh between 2 and 4 pounds. If it is a
variable weight product which is sold for a single standard price rather than a price based on a random weight, you
should have listed the range of weights in the basic description so the customers know what they are getting.  The
customer needs this information to know how much to order.</p>

<div id="minimum_weight"></div><div id="maximum_weight"></div>
<h3>Minimum/Maximum Weights</h3>
<p>This applies only to products that require weighing at the time of purchase. A minimum and maximum weight provides the
customer with a general idea of what to expect.  It will also allow the customer to let you know if they would like a
smaller or larger item.  These can be approximate, but the more accurate the range, the more informed the customer will
be.  Note that the minimum and maximum weight must be in the same unit as the pricing unit.  So if your product is
$5.00/pound, enter minimum and maximum weights in pounds (or decimal/fractions thereof).  For example: a whole chicken
might min. weight = 3 pounds / max. weight = 5 pounds.   A steak might range 8 to 10 ounces but if it is sold by the
pound, you would list min. weight = .5 pounds / max. weight = .625 pounds. When it is time to fill orders, any weights
outside the minimum/maximum will be rejected, so you should be as permissive as necessary with the numbers.</p>

<div id="meat_weight_type"></div>
<h3>Meat Weight/Type</h3>
<p>Selecting one of these will automatically insert the following text with the option in caps into your product
description: "You will be billed for exact LIVE weight." Use this if your product is a random weight item and
it is not clear from the name what type it is.</p>

<div id="production_type_id"></div>
<h3>Production Type</h3>
<p>If a product is certified organic, all natural, or otherwise designated, this can be chosen from the drop down box.
If marking a product as certified in some way, please be sure the certification is officially registered.</p>

<div id="storage_type"></div>
<h3>Storage Type</h3>
<p>Choose the storage requirements for this product.  It is important to classify how the product must be transported
and stored on delivery day.</p>

<div id="operation"></div>
<h3>Operation</h3>
<p><h4>Add Product:</h4>Select this button to add your new product to the products list. It will not be
available for members to purchase until confirmed by an adminstrator.</p>
<p><h4>Save as New:</h4>Select this checkbox to save your changes as a new product while leaving
the original product unchanged.  This is an easy way to clone a product with only minor changes.  The new product
will not include any picture from the current product.</p>
<p><h4>Update Product:</h4>Select this button to save your changes to the products list. Depending what
changes were made, the product may need confirmation by an administrator before it is available for members to
purchase.</p>
<p><h4>Cancel:</h4>Select this button to ignore any changes you have made and return to the previous screen.</p>


<!--
<div id="future"></div>
<h3>Future Deliveries</h3>
If the product is one that is being sold in advance but will not actually be delivered until a future order cycle,
let us know the date that it will be delivered.  This must be the date of an existing delivery.  If you are not
sure about the future delivery date, please contact us to discuss this.  If this is an item where you will be setting
up more than one payment, you will need to contact us to discuss this.  Also, contact us if the item will be delivered
directly to the customer by you and not through the <?php echo ORGANIZATION_TYPE; ?> so that we can help you work out
the details of listing the item.  Contact us at <a href="mailto:<?php echo HELP_EMAIL; ?>"><?php echo HELP_EMAIL; ?></a>.
-->


</p>
<!-- CONTENT ENDS HERE -->
<br>
<div align="right">
<a href="#" onClick="window.close()">Close Window</a>
</div>
</body>
</html>


