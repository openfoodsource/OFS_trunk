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

function reset_delivery_list() {
  c_arrElements = getElementsByClass("del_complete");
  for (i = 0; i < c_arrElements.length; i++) {
    if (c_arrElements[i].attributes["class"].value == 'del_complete') {
      // Change the class from "complete" to "del_incomplete"
      c_arrElements[i].attributes["class"].value = "del_incomplete";
      }
    }
  }

function delivery_generate_start() {
  //get list of all span elements:
  c_arrElements = getElementsByClass("del_incomplete");
  // Set display elements
  document.getElementById("delivery_progress").style.display = "block";
  i = 0;
  }

function generate_basket_list() {
  //iterate over the <li> array elements:
  if (i < c_arrElements.length) {
    //check that this is the proper class
    if (c_arrElements[i].attributes["class"].value == 'del_incomplete') {
      // Get the id of the element (that is the delivery_id, formatted like: delivery_id:9
      var element_id = c_arrElements[i].attributes["id"].value;
      document.getElementById('delivery_id').value = element_id.substr(12);
      $.post("create_basket_items_products_tables.php", {
        ajax:"yes",
        process:"get_product_list",
        delivery_id:element_id.substr(12)
        },
      function(delivery_data) {
        document.getElementById("basketList").innerHTML = delivery_data;
        var c_progress_left = Math.floor (300 * i / c_arrElements.length);
        var c_progress_right = 300 - c_progress_left;
        document.getElementById("c_progress-left").style.width = c_progress_left+"px";
        document.getElementById("c_progress-left").innerHTML = Math.floor (c_progress_left / 3)+"%&nbsp;";
        document.getElementById("c_progress-right").style.width = c_progress_right+"px";
        document.getElementById(element_id).className = "del_complete";
      i++;
//       reset_basket_list();
      product_generate_start();
      process_product_list();
        });
      }
    }
  else {
    document.getElementById("c_progress-left").style.width = "300px";
    document.getElementById("c_progress-left").innerHTML = "100%&nbsp;";
    document.getElementById("c_progress-right").style.width = "0px";
    }
  }

// PRODUCT FUNCTIONS

function product_generate_start() {
  //get list of all span elements:
  p_arrElements = getElementsByClass("bpid_incomplete");
  j = 0;
  }

function process_product_list() {
  //iterate over the <li> array elements:
  if (j < p_arrElements.length) {
    //check that this is the proper class
    if (p_arrElements[j].attributes["class"].value == 'bpid_incomplete' && document.getElementById("pause").checked == false) {
      // Get the id of the element (that is the delivery_id, formatted like: delivery_id:9
      var element_id = p_arrElements[j].attributes["id"].value;
      $.post("create_basket_items_products_tables.php", {
        ajax:"yes",
        process:"process_product",
        bpid:element_id.substr(5)
        },
      function(basket_data) {
        // Return codes: First ten characters reserved for status/command
        //               Second and third ten characters are reserved for arguments
        if (basket_data.substr(0,10) == 'PAUSE     ') { // return code = pause
          document.getElementById("pause").checked = true;
          // Send the data to the process_target <div>
          document.getElementById("process_target").innerHTML += basket_data.substr(30); // first 30 characters = return code
          }
        else {
          j++;             
          document.getElementById("process_target").innerHTML = document.getElementById("process_target").innerHTML.substr(-1000)+basket_data.substr(30);
          // Update the progress bar
          var p_progress_left = Math.floor (300 * j / p_arrElements.length);
          var p_progress_right = 300 - p_progress_left;
          document.getElementById("p_progress-left").style.width = p_progress_left+"px";
          document.getElementById("p_progress-left").innerHTML = Math.floor (p_progress_left / 3)+"%&nbsp;";
          document.getElementById("p_progress-right").style.width = p_progress_right+"px";
          document.getElementById(element_id).className = "bpid_complete";
          process_product_list();
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