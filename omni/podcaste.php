
<div id="id01"></div>
<script>

var getJSON = function(url, callback) {

    var xhr = new XMLHttpRequest();
    xhr.open('GET', url, true);
    xhr.responseType = 'json';
    
    xhr.onload = function() {
    
        var status = xhr.status;
        
        if (status == 200) {
            callback(null, xhr.response);
        } else {
            callback(status);
        }
    };
    
    xhr.send();
};

getJSON('https://omny.fm/api/orgs/4628fc36-1d90-4922-817d-aac0000aff55/programs/<?php echo $_GET[qry] ?>/clips',  function(err, data) {
    
  
  var out = '<section class="tiles" >';
  var i;
  var res
  for(i = 0; i < data.Clips.length; i++) {
   
   
   
   res = data.Clips[i].EmbedUrl.toString();
   //res=res.substring(0,120)+'...';
   res=(res.substring(8,1200))
   
	out+='<article class="style1"  ><span class="image"><img src="'+data.Clips[i].ImageUrl+'" alt="" /></span><a target="myIframe" href="http://www.cwnsports.com/omni/play.php?qry='+res+'"<h2>Episode: '+data.Clips[i].Episode+'</h2><div class="content"><p>'+res+'</p></div></a></article>'	
	
  }
  out +='</section>';
  

	
document.getElementById("program").innerHTML = out;	
});

</script>
<!DOCTYPE HTML>
<!--
	Phantom by HTML5 UP
	html5up.net | @ajlkn
	Free for personal and commercial use under the CCA 3.0 license (html5up.net/license)
-->
<html>
	<head>
		<title>Phantom by HTML5 UP</title>
		<meta charset="utf-8" />
		<meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no" />
		<link rel="stylesheet" href="assets/css/main.css" />
		<noscript><link rel="stylesheet" href="assets/css/noscript.css" /></noscript>
	</head>
	<body class="is-preload">
		<!-- Wrapper -->
			<div id="wrapper">

				<!-- Header -->
					

				<!-- Menu -->
					
				<!-- Main -->
					<div id="main" >
						<div class="inner">
							<header>
								<h1>Episode<br />
								</h1>
								
							</header>
                            <div id="program" >
							
				

			</div>

		<!-- Scripts -->
			<script src="assets/js/jquery.min.js"></script>
			<script src="assets/js/browser.min.js"></script>
			<script src="assets/js/breakpoints.min.js"></script>
			<script src="assets/js/util.js"></script>
			<script src="assets/js/main.js"></script>

	</body>
</html>