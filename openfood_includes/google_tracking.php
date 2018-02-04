<?php

// NOTICE!!!  --  If using Wordpress, also check Wordpress template for a copy of this code

if (substr(GOOGLE_TRACKING_ID, 0, 3) == 'UA-')
  {
    $google_tracking_code = '
<script>
  (function(i,s,o,g,r,a,m){i["GoogleAnalyticsObject"]=r;i[r]=i[r]||function(){
  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
  })(window,document,"script","//www.google-analytics.com/analytics.js","ga");
  ga("create", "'.GOOGLE_TRACKING_ID.'", "auto");
  ga("send", "pageview");
</script>';
  }
elseif (substr(GOOGLE_TRACKING_ID, 0, 3) == 'GTM')
  {
    $google_tracking_code = '
<!-- Google Tag Manager -->
<noscript><iframe src="//www.googletagmanager.com/ns.html?id='.GOOGLE_TRACKING_ID.'"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({"gtm.start":
new Date().getTime(),event:"gtm.js"});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!="dataLayer"?"&l="+l:"";j.async=true;j.src=
"//www.googletagmanager.com/gtm.js?id="+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,"script","dataLayer","'.GOOGLE_TRACKING_ID.'");</script>
<!-- End Google Tag Manager -->';
  }
