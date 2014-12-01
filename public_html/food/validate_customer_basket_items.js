var c_arrElements;
var p_arrElements;
var i;
var j;

function getElementsByClass (needle) {
  var my_array = document.getElementsByTagName("li");
  var retvalue = new Array();
  var i;
  var j;
  for (i = 0, j = 0; i < my_array.length; i++) {
    var c = " " + my_array[i].className + " ";
    if (c.indexOf(" " + needle + " ") != -1)
      retvalue[j++] = my_array[i];
    }
  return retvalue;
  }

// DELIVERY FUNCTIONS

function reset_delivery_list(target_delivery) {
  c_arrElements = getElementsByClass("del_row");
  for (i = 0; i < c_arrElements.length; i++) {
    if (i >= target_delivery & c_arrElements[i].attributes["class"].value == 'del_complete del_row') {
      // Change the class from "del_complete" to "del_incomplete"
      c_arrElements[i].attributes["class"].value = "del_incomplete del_row";
      }
    else if(i < target_delivery & c_arrElements[i].attributes["class"].value == 'del_incomplete del_row') {
      // Change the class from "del_incomplete" to "del_complete"
      c_arrElements[i].attributes["class"].value = "del_complete del_row";
      }
    }
  document.getElementById("pause").checked = false;
  document.getElementById("process_target").innerHTML = "";
  }
function delivery_generate_start(target_delivery) {
  //get list of all span elements:
  c_arrElements = getElementsByClass("del_row");
  // Set display elements
  document.getElementById("delivery_progress").style.display = "block";
  i = target_delivery;
  }

function generate_basket_list() {
  //iterate over the <li> array elements:
  if (i < c_arrElements.length) {
    //check that this is the proper class
    if (c_arrElements[i].attributes["class"].value == 'del_incomplete del_row') {
      // Get the id of the element (that is the delivery_id, formatted like: delivery_id:9
      var element_id = c_arrElements[i].attributes["id"].value;
      document.getElementById('delivery_id').value = element_id.substr(12);
      $.post("validate_customer_basket_items.php", {
        ajax:"yes",
        process:"get_basket_list",
        delivery_id:element_id.substr(12)
        },
      function(delivery_data) {
        document.getElementById("basketList").innerHTML = delivery_data;
        var c_progress_left = Math.floor (300 * i / c_arrElements.length);
        var c_progress_right = 300 - c_progress_left;
        document.getElementById("c_progress-left").style.width = c_progress_left+"px";
        document.getElementById("c_progress-left").innerHTML = Math.floor (c_progress_left / 3)+"%&nbsp;";
        document.getElementById("c_progress-right").style.width = c_progress_right+"px";
        document.getElementById(element_id).className = "del_complete del_row";
      i++;
//       reset_basket_list();
      basket_generate_start();
      process_basket_list();
        });
      }
    }
  else {
    document.getElementById("c_progress-left").style.width = "300px";
    document.getElementById("c_progress-left").innerHTML = "100%&nbsp;";
    document.getElementById("c_progress-right").style.width = "0px";
    }
  }

// BASKET FUNCTIONS

function basket_generate_start() {
  //get list of all span elements:
  p_arrElements = getElementsByClass("basket_incomplete");
  j = 0;
  }

function process_basket_list() {
  //iterate over the <li> array elements:
  if (j < p_arrElements.length) {
    //check that this is the proper class
    if (p_arrElements[j].attributes["class"].value == 'basket_incomplete' && document.getElementById("pause").checked == false) {
      // Get the id of the element (that is the delivery_id, formatted like: delivery_id:9
      var element_id = p_arrElements[j].attributes["id"].value;
//      alert ("HERE");
      $.post("validate_customer_basket_items.php", {
        ajax:"yes",
        process:"process_basket",
        basket_id:element_id.substr(12),
        product_id:document.getElementById("product_id").value
        },
      function(basket_data) {
        // Return codes: First ten characters reserved for status/command
        //               Second and third ten characters are reserved for arguments
        if (basket_data.substr(0,10) == 'PAUSE     ') { // return code = pause
          document.getElementById("pause").checked = true;
          document.getElementById("basket_id").value = parseInt(basket_data.substr(10,10))
          document.getElementById("product_id").value = parseInt(basket_data.substr(20,10))
          // Send the data to the process_target <div>
          document.getElementById("process_target").innerHTML = basket_data.substr(30); // first 10 characters = return code
          }
        else {
          j++;             
          // Update the progress bar
          var p_progress_left = Math.floor (300 * j / p_arrElements.length);
          var p_progress_right = 300 - p_progress_left;
          document.getElementById("p_progress-left").style.width = p_progress_left+"px";
          document.getElementById("p_progress-left").innerHTML = Math.floor (p_progress_left / 3)+"%&nbsp;";
          document.getElementById("p_progress-right").style.width = p_progress_right+"px";
          document.getElementById(element_id).className = "basket_complete";
          document.getElementById("product_id").value = 0;
          process_basket_list();
          }
        });
      }
    }
  else {
    document.getElementById("p_progress-left").style.width = "300px";
    document.getElementById("p_progress-left").innerHTML = "100%&nbsp;";
    document.getElementById("p_progress-right").style.width = "0px";
    generate_basket_list();
    }
  }

function update_db(basket_id, product_id, field, target_value) {
  alert (basket_id+' '+product_id+' '+field+' '+target_value);
  }

function transfer_to_field(source_field, target_field) {
  var target_field;
  var source_field;
  document.getElementById(target_field).value = document.getElementById(source_field).innerHTML;
  }
function skip_continue () {
  var basket_id;
  var product_id;
  document.getElementById("pause").checked = false;
  alert ('BASKET: '+basket_id+' PRODUCT: '+product_id);
  process_basket_list();
  }

function update_db (update_field) {
  var source_id = 'update_'+update_field;
  $.post("validate_customer_basket_items.php", {
    ajax:"yes",
    process:"update_db",
    delivery_id:document.getElementById('delivery_id').value,
    basket_id:document.getElementById('basket_id').value,
    product_id:document.getElementById('product_id').value,
    update_field:update_field,
    update_content:document.getElementById(source_id).value
    },
  function(query_result) {
    // Return codes: First ten characters reserved for status/command
    //               Second and third ten characters are reserved for arguments
    if (query_result.substr(0,10) == 'SUCCESS   ') { // return code = success
      document.getElementById('status').innerHTML = query_result.substr(10);
      }
    else {
      document.getElementById('status').innerHTML = query_result;
      document.getElementById('skip').innerHTML = ' &nbsp; &nbsp; <br>Continue<br> &nbsp; &nbsp; ';
      // Fail condition
      }
    });
  }

