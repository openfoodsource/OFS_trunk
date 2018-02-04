function form_auto_fill ()
{
  var error_message = "Auto-fill failed for the members and information below:\n\n";
  var is_error = false;
  var raw_data = document.getElementById("auto_fill_box").value;
  var batchno = document.getElementById("auto_fill_batchno").value;
  var lines = raw_data.split(/\r?\n/g);
  for (var i = 0; i < lines.length; i++)
  {
    if (lines[i].length > 0)
    {
      var values = lines[i].split(/ ?\t ?/);
      values[0] = values[0].replace(/[^\d]/g, "");
      values[1] = values[1].replace(/[^\d.-]/g, "");
      values[2] = values[2].replace(/[^\d.-]/g, "");
      var shopping_ref = document.getElementById("shopping_amount"+values[0]);
      var membership_ref = document.getElementById("membership_amount"+values[0]);
      if (shopping_ref && membership_ref && (values[1] || values[2]))
      {
        shopping_ref.value = values[1];
        membership_ref.value = values[2];
        document.getElementById("batchno"+values[0]).value = batchno;
      }
      else if (values[0] || values[1] || values[2])
      {
        if (! values[0])
        {
          values[0] = " ??";
        }
        if (! values[1])
        {
          values[1] = "--";
        }
        if (! values[2])
        {
          values[2] = "--";
        }
        error_message = error_message+"\t#"+values[0]+"        ( "+values[1]+" / "+values[2]+" )\n";
        is_error = true;
      }
    }
  }
  if (is_error)
  {
    alert (error_message);
  }
  else
  {
    alert ("Auto-fill completed.");
  }
}
