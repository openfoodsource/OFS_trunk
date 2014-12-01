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
      $.post("create_ledger.php", {
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
        document.getElementById(element_id).className = "del_complete";
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
        $.post("create_ledger.php", {
        ajax:"yes",
        process:"process_basket",
        basket_id:element_id.substr(12),
        // product_id:document.getElementById("basket_id").value
        },
      function(basket_data) {
        // Return codes: First ten characters reserved for status/command
        //               Second and third ten characters are reserved for arguments
        if (basket_data.substr(0,10) == 'PAUSE     ') { // return code = pause
          document.getElementById("pause").checked = true;
          document.getElementById("basket_id").value = parseInt(basket_data.substr(10,10))
          // Send the data to the process_target <div>
          document.getElementById("process_target").innerHTML = basket_data.substr(30); // first 10 characters = return code
          }
        else if (basket_data.substr(0,10) == 'OKAY      ') { // return code = okay
          j++;             
          // Update the progress bar
          var p_progress_left = Math.floor (300 * j / p_arrElements.length);
          var p_progress_right = 300 - p_progress_left;
          document.getElementById("p_progress-left").style.width = p_progress_left+"px";
          document.getElementById("p_progress-left").innerHTML = Math.floor (p_progress_left / 3)+"%&nbsp;";
          document.getElementById("p_progress-right").style.width = p_progress_right+"px";
          document.getElementById(element_id).className = "basket_complete";
          process_basket_list();
          }
        else {
          document.getElementById("process_target").innerHTML = basket_data.substr(30);
          alert (basket_data);
          j++;
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
