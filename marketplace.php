<?php
/**
 * Name: Marketplace App
 * Description: Simple marketplace application with bitcoin wallet support
 * Version: 1.0
 * Author: Super Testnet <https://highlevelbitcoin.com/>
 */
use Friendica\Core\Hook;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Profile;
use Friendica\Model\User;

function marketplace_install() {
	Hook::register( 'network_tabs', 'addon/marketplace/marketplace.php', 'tab_addition' );
	Hook::register( 'page_header', 'addon/marketplace/marketplace.php', 'marketplace_alterheader' );
	makeMarketplaceTables();
}

function tab_addition( $a, &$b ) {
        $b[ 'tabs' ][] = [
		'label'	=> DI::l10n()->t( 'Marketplace' ),
		'url'	=> 'marketplace/',
		'sel'	=> $selectedTab == 'marketplace' ? 'active' : '',
		'title'	=> DI::l10n()->t( 'View marketplace' ),
		'id'	=> 'marketplace-tab',
		'accesskey' => 'v',
	];
}

function marketplace_alterheader( $a, &$navHtml ) {
	$username = getUsernameById( getCurrentUserId() );
	$adminScriptTag = '';
	if ( is_site_admin() ) {
		$adminScriptTag = '
			<script>
			$(document).ready(function(){
				var divider = document.createElement( "li" );
				divider.setAttribute( "role", "presentation" );
				divider.className = "divider";
				var li = document.createElement( "li" );
				li.setAttribute( "role", "presentation" );
				var anchor = document.createElement( "a" );
				anchor.setAttribute( "accesskey", "a" );
				anchor.setAttribute( "role", "menuitem" );
				anchor.id = "nav-admin-link";
				anchor.className = "nav-link";
				anchor.href = "marketplace/admin/";
				anchor.title = "Configure settings for the marketplace plugin";
				var icon = document.createElement( "i" );
				icon.className = "fa fa-shopping-basket fa-fw";
				icon.setAttribute( "aria-hidden", "true" );
				var text = document.createTextNode( " Marketplace admin" );
				anchor.append( icon );
				anchor.append( text );
				li.append( anchor );
				document.getElementsByClassName( "menu-popup" )[ 1 ].insertBefore( li, document.getElementById( "nav-admin-link" ).parentElement );

				var li2 = document.createElement( "li" );
				li2.setAttribute( "role", "presentation" );
				li2.className = "list-group-item";
				var anchor2 = document.createElement( "a" );
				anchor2.setAttribute( "role", "menuitem" );
				anchor2.className = "nav-link";
				anchor2.href = "marketplace/admin/";
				anchor2.title = "Configure settings for the marketplace plugin";
				var icon2 = document.createElement( "i" );
				icon2.className = "fa fa-shopping-basket fa-fw";
				icon2.setAttribute( "aria-hidden", "true" );
				var text2 = document.createTextNode( " Marketplace admin" );
				anchor2.append( icon2 );
				anchor2.append( text2 );
				li2.append( anchor2 );
				document.getElementsByClassName( "nav-container" )[ 0 ].getElementsByTagName( "ul" )[ 0 ].insertBefore( li2, document.getElementsByClassName( "nav-container" )[ 0 ].getElementsByTagName( "ul" )[ 0 ].lastChild.previousElementSibling.previousElementSibling );
			});
			</script>
		';
	}
	$addScriptTag = '
		<script>
			$(document).ready(function(){
			        var tab0 = document.createElement( "li" );
			        tab0.role = "presentation";
			        tab0.id = "marketplace";
			        var link0 = document.createElement( "a" );
			        link0.role = "menuitem";
			        link0.href = "/marketplace/";
			        link0.title = "View marketplace";
			        link0.innerText = "Marketplace";
			        tab0.append( link0 );
			        if ( document.getElementsByTagName( "title" )[ 0 ].innerText.includes( " | Community" ) ) {
					document.getElementsByClassName( "tabs" )[ 0 ].append( tab0 );
//                                        var i; for ( i=0; i<document.getElementsByClassName( "tabs" ).length; i++ ) {
  //                                              if ( !document.getElementsByClassName( "tabs" )[ i ].getElementsByClassName( "dropdown-menu" )[ 0 ] || window.innerWidth > 600 ) {
    //                                                    document.getElementsByClassName( "tabs" )[ i ].append( tab0 );
    //                                            } else {
      //                                                  document.getElementsByClassName( "tabs" )[ i ].getElementsByClassName( "dropdown-menu" )[ 0 ].append( tab0 );
        //                                        }
          //                              }
			        }
			        var tab1 = document.createElement( "li" );
			        tab1.role = "presentation";
			        tab1.id = "marketplace";
			        var link1 = document.createElement( "a" );
			        link1.role = "menuitem";
			        link1.href = "/marketplace/my-store/";
			        link1.title = "View your store";
			        link1.innerText = "My store";
			        tab1.append( link1 );
			        if ( window.location.href.includes( "' . $username . '" ) ) {
				        document.getElementsByClassName( "tabs" )[ 0 ].append( tab1 );
//					var i; for ( i=0; i<document.getElementsByClassName( "tabs" ).length; i++ ) {
//						if ( !document.getElementsByClassName( "tabs" )[ i ].getElementsByClassName( "dropdown-menu" )[ 0 ] || window.innerWidth > 600  ) {
//				                	document.getElementsByClassName( "tabs" )[ i ].append( tab0 );
//						} else {
//							document.getElementsByClassName( "tabs" )[ i ].getElementsByClassName( "dropdown-menu" )[ 0 ].append( tab0 );
//						}
//					}
			        }
			});
		</script>
	' . "\r\n";
	DI::page()[ 'htmlhead' ] .= $addScriptTag;
	DI::page()[ 'htmlhead' ] .= $adminScriptTag;
}

function marketplace_module() {}

function marketplace_init($a) {

$x = <<< EOT
	<!-- Any html or javascript added here will go in the header of your homepage -->

	<script type="text/javascript">var link = document.createElement("link");link.rel="stylesheet";link.href="https://unpkg.com/leaflet@1.5.1/dist/leaflet.css";link.integrity="sha512-xwE/Az9zrjBIphAcBb3F6JVqxf46+CDLwfLMHloNu6KEQCAWi6HcDUbeOfBIptF7tcCzusKFjFw2yuvEpDL9wQ==";link.setAttribute("crossorigin", "");document.getElementsByTagName("head")[0].appendChild(link);</script>

	<script type="text/javascript">var style = document.createElement("style");var css = document.createTextNode("#mapid {height: 486px;}");style.appendChild(css);document.getElementsByTagName("head")[0].appendChild(style);</script>

	<script src="https://unpkg.com/leaflet@1.5.1/dist/leaflet.js" integrity="sha512-GffPMF3RvMeYyc1LWMHtK8EbPv0iNZ8/oTtHPx9/cc2ILxQ+u905qIwdpULaqDkyBKgOaB57QTMg7ztg8Jm2Og==" crossorigin=""></script>

	<script>
		$(document).ready(function(){
	                var wrapper = document.createElement( "div" );
	                wrapper.className = "tabbar-wrapper";
	                var tabbar = document.createElement( "ul" );
	                tabbar.className = "tabbar list-inline visible-lg visible-md visible-sm visible-xs";
	                var menuitem = document.createElement( "li" );
			menuitem.style.width = "100%";
	                var tabs = document.createElement( "ul" );
	                tabs.className = "tabs flex-nav";
	                tabs.role = "menu";
			var tab0 = document.createElement( "li" );
			tab0.role = "presentation";
			tab0.id = "all-products";
                        var tab1 = document.createElement( "li" );
                        tab1.role = "presentation";
                        tab1.id = "map";
			var tab2 = document.createElement( "li" );
	                tab2.role = "presentation";
	                tab2.id = "my-store";
	                var tab3 = document.createElement( "li" );
	                tab3.role = "presentation";
	                tab3.id = "new-product";
	                var tab4 = document.createElement( "li" );
	                tab4.role = "presentation";
	                tab4.id = "wallet";
			var link0 = document.createElement( "a" );
			link0.role = "menuitem";
			link0.href = "/marketplace/";
			link0.title = "View all products";
			link0.innerText = "All products";
                        var link1 = document.createElement( "a" );
                        link1.role = "menuitem";
                        link1.href = "/marketplace/map/";
                        link1.title = "View product map";
                        link1.innerText = "Map";
	                var link2 = document.createElement( "a" );
	                link2.role = "menuitem";
	                link2.href = "/marketplace/my-store/";
	                link2.title = "View and manage your products";
	                link2.innerText = "My store";
	                var link3 = document.createElement( "a" );
	                link3.role = "menuitem";
	                link3.href = "/marketplace/new-product/";
	                link3.title = "Create a new product";
	                link3.innerText = "New product";
	                var link4 = document.createElement( "a" );
	                link4.role = "menuitem";
	                link4.href = "/marketplace/wallet/";
	                link4.title = "View your bitcoin wallet";
	                link4.innerText = "Wallet";
			tab0.append( link0 );
                        tab1.append( link1 );
	                tab2.append( link2 );
//	                tab3.append( link3 );
	                tab4.append( link4 );
			tabs.append( tab0, tab1, tab2, tab3, tab4 );
	                menuitem.append( tabs );
	                tabbar.append( menuitem );
	                wrapper.append( tabbar );
	                document.getElementById( "tabmenu" ).append( wrapper );
                        document.getElementById( "tabmenu" ).style.width = "100%";
		});
	</script>
EOT;
	DI::page()['htmlhead'] .= $x;

}

function marketplace_content($app) {
	$base = DI::baseUrl()->get();

	$o = '';

	if ( strpos( $_SERVER['REQUEST_URI'], '/admin/' ) > -1 && is_site_admin() ) {
                $r = q( "SELECT * FROM lnbitsadmin" );
                if ( DBA::isResult( $r ) ) {
                        $data = json_encode( $r );
                }
		if ( $_POST[ "lnbits-admin-user" ] && $_POST[ "lnbits-admin-key" ] && $_POST[ "lnbits-domain" ] ) {
                        $domain = $_POST[ "lnbits-domain" ];
                        $user = $_POST[ "lnbits-admin-user" ];
			$key = $_POST[ "lnbits-admin-key" ];
                	$fields[ 'lnbits-domain' ] = filter_var( $domain, FILTER_SANITIZE_STRING );
                	$fields[ 'lnbits-admin-user' ] = filter_var( $user, FILTER_SANITIZE_STRING );
                	$fields[ 'lnbits-admin-key' ] = filter_var( $key, FILTER_SANITIZE_STRING );
			if ( empty( $data ) ) {
				DBA::insert( 'lnbitsadmin', $fields );
			} else {
				DBA::update( 'lnbitsadmin', $fields, [ 'id' => 1 ] );
			}
                }
                $r = q( "SELECT * FROM lnbitsadmin" );
                if ( DBA::isResult( $r ) ) {
                        $data = json_encode( $r );
                }
		$o .= <<<EOT
		        <form method="post">
	                	<p style="font-weight: bold;">Lnbits domain</p>
	                	<input type="text" id="lnbits-domain" name="lnbits-domain"><br><br>
	                	<p style="font-weight: bold;">Lnbits admin user</p>
	                	<input type="text" id="lnbits-admin-user" name="lnbits-admin-user"><br><br>
				<p style="font-weight: bold;">Lnbits admin key</p>
				<input type="text" id="lnbits-admin-key" name="lnbits-admin-key"><br><br>
	        	        <input type="submit" id="submit" name="submit">
			</form>
			<script>
				var json = $data;
				document.getElementById( "lnbits-domain" ).value = json[ 0 ][ "lnbits-domain" ];
				document.getElementById( "lnbits-admin-user" ).value = json[ 0 ][ "lnbits-admin-user" ];
				document.getElementById( "lnbits-admin-key" ).value = json[ 0 ][ "lnbits-admin-key" ];
			</script>
EOT;
	}

	if ( strpos( $_SERVER['REQUEST_URI'], '/receipt/' ) > -1 ) {
		$product = $_GET[ "product" ];
		$pmthash = $_GET[ "pmthash" ];
		$lnbits_url = $_GET[ "lnbitsurl" ];
                $r = q( "SELECT * FROM products WHERE hash = '$product'" );
                if ( DBA::isResult( $r ) ) {
                        $data = json_encode( $r[ 0 ] );
                }
		$userid = $r[ 0 ][ "uid" ];
		$apikey = q( "SELECT `lnbits-inv-key` FROM wallets WHERE uid = $userid" );
                if ( DBA::isResult( $apikey ) ) {
                        $key = $apikey[ 0 ][ "lnbits-api-key" ];
			$invkey = $apikey[ 0 ][ "lnbits-inv-key" ];
                }
		$creator = getUsernameById( $userid );
		$name = $r[ 0 ][ "name" ];
		$description = $r[ 0 ][ "descr" ];
		$price = "$" . $r[ 0 ][ "price" ] / 100 . " (USD)";
		$amount = usdToSats( $r[ 0 ][ "price" ] );
	        $o .=  <<< EOT
		<div id="yespage" style="display: none;">
		        <h3>Receipt for $name</h3>
			<p>This receipt confirms that you have purchased $name with this description:</p>
			<div class="description" style="margin-left: 50px;">$description</div>
			<p>The product was purchased for $price. Please contact <a href="$base/profile/$creator"> the seller ($creator)</a> for more details.</p>
			<p>Your lightning wallet should provide you with a proof of payment corresponding to this payment hash:</p>
			<p style="margin-left: 50px;">$pmthash</p>
			<p>The seller also has a copy of your proof of payment.</p>
		</div>
		<div id="nopage" style="display: none;">
		        <h3>Sorry!</h3>
			<p>It looks like you have not yet purchased $name. Please try again later.</p>
		</div>
		<script type="text/javascript">
			var style = document.createElement( "style" );
			var css = document.createTextNode( ".description p {margin: 0px;}" );
			style.appendChild( css );
			document.getElementsByTagName( "head" )[ 0 ].appendChild( style );
		</script>
		<script>
			var pmthash = "$pmthash";
			function checkPmtStatus( pmthash ) {
				var url = "$base/addon/marketplace/marketplace.php?action=checkpmtstatus&pmthash=" + pmthash + "&invkey=$invkey&lnbitsurl=$lnbits_url";
				console.log( url );
                		var xhttp = new XMLHttpRequest();
                		xhttp.onreadystatechange = function() {
                	        	if ( this.readyState == 4 && this.status == 200 ) {
                	                	if ( this.responseText == 1 ) {
							document.getElementById( "yespage" ).style.display = "block";
	        	                       	} else {
	        	               	                document.getElementById( "nopage" ).style.display = "block";
	        	       	                }
	        	                }
			               };
	        	       	xhttp.open( "GET", url, true );
	        	        xhttp.send();
			}
			checkPmtStatus( pmthash );
		</script>
EOT;
	}

	if ( strpos( $_SERVER['REQUEST_URI'], '/details/' ) > -1 ) {
		$product = $_GET[ "product" ];
                $r = q( "SELECT * FROM products WHERE hash = '$product'" );
                if ( DBA::isResult( $r ) ) {
                        $data = json_encode( $r[ 0 ] );
                }
		$userid = $r[ 0 ][ "uid" ];
		$apikey = q( "SELECT `lnbits-api-key`, `lnbits-inv-key` FROM wallets WHERE uid = $userid" );
                if ( DBA::isResult( $apikey ) ) {
                        $key = $apikey[ 0 ][ "lnbits-api-key" ];
			$invkey = $apikey[ 0 ][ "lnbits-inv-key" ];
                }
                $lnbitsdata = q( "SELECT * FROM lnbitsadmin" );
                if ( DBA::isResult( $lnbitsdata ) ) {
                        $lnbitsdata = json_encode( $lnbitsdata );
                }
		$field = "lnbits-domain";
		$lnbits_url = json_decode( $lnbitsdata )[ 0 ]->$field;
		$creator = getUsernameById( $userid );
		$name = $r[ 0 ][ "name" ];
		$description = htmlspecialchars_decode( $r[ 0 ][ "descr" ] );
		$latlong = $r[ 0 ][ "latlong" ];
		$price = "$" . $r[ 0 ][ "price" ] / 100 . " (USD)";
		$amount = usdToSats( $r[ 0 ][ "price" ] );
		$memo = $name;
		$lnbits_apikey = $key;
		$invoice = requestInvoice( $lnbits_url, $amount, $memo, $lnbits_apikey );
		$showInvoice = showInvoice( $lnbits_url, $invoice, $invkey, $product );
		$buybtn = "";
		if ( $r[ 0 ][ "buynow" ] == 1 ) {
			$buybtn = <<<EOT
			<div><a><button class="btn btn-default buybtn">Buy now</button></a></div>
                        <script>
                                var style = document.createElement( "style" );
                                var css = document.createTextNode( ".buybtn {background-color: #fefefe;}" );
                                style.appendChild( css );
                                document.getElementsByTagName( "head" )[ 0 ].appendChild( style );
                        </script>
			<script>
				sessionStorage[ "toggle" ] = 0;
				document.getElementsByClassName( "buybtn" )[ 0 ].addEventListener( "click", function() {
					if ( sessionStorage[ "toggle" ] == 0 ) {
						sessionStorage[ "toggle" ] = 1;
						document.getElementById( "lnurl-auth-qr" ).style.display = "block";
					} else {
						sessionStorage[ "toggle" ] = 0;
						document.getElementById( "lnurl-auth-qr" ).style.display = "none";
					}
				});
			</script>
EOT;
		}
	        $o .=  <<< EOT
	        <h3>$name</h3>
		<div class="description">$description</div><br><hr style="border: 1px solid brown;" /><br>
		<p>Created by <a href="$base/profile/$creator">$creator</a></p>
		<p>Located at $latlong</p>
		<p>Price: $price</p>
		<p>$buybtn</p>
		<p>$showInvoice</p>
		<script type="text/javascript">
			var style = document.createElement( "style" );
			var css = document.createTextNode( ".description p {margin: 0px;}" );
			style.appendChild( css );
			document.getElementsByTagName( "head" )[ 0 ].appendChild( style );
		</script>
EOT;
	}

	if ( strpos( $_SERVER['REQUEST_URI'], '/my-store/' ) > -1 ) {
                $uid = getCurrentUserId();
                $r = q( "SELECT * FROM store WHERE uid = $uid" );
                if ( DBA::isResult( $r ) ) {
                        $data = json_encode( $r );
                }
/*
		if ( empty( $data ) && getCurrentUserId() != 0 && $_POST[ "store-name" ] && $_POST[ "description" ] && $_POST[ "latitude" ] && $_POST[ "longitude" ] ) {
                        $storename = $_POST[ "store-name" ];
                        $description = $_POST[ "description" ];
			$latlong = $_POST[ "latitude" ] . "," . $_POST[ "longitude" ];
			$fields[ 'uid' ] = $uid;
                	$fields[ 'name' ] = filter_var( $storename, FILTER_SANITIZE_STRING );
                	$fields[ 'descr' ] = filter_var( $description, FILTER_SANITIZE_STRING );
                	$fields[ 'latlong' ] = filter_var( $latlong, FILTER_SANITIZE_STRING );
                	DBA::insert( 'store', $fields );
                }
		$r = q( "SELECT * FROM store WHERE uid = $uid" );
                if ( DBA::isResult( $r ) ) {
                        $data = json_encode( $r[ 0 ] );
                }
		if ( empty( $data ) && getCurrentUserId() != 0 ) {
		        $o .=  <<< EOT
		        <h3>Create your store</h3>
			<div id="instructions" style="padding: 10px; border: 3px solid black;">
				<p style="font-weight: bold;">Instructions</p>
				<p>Enter a name and description for your store. You'll also need a latitude and a longitude which help us place a pin on our map. Get your latitude and longitude from <a href="https://latlong.net/" target="_blank">latlong.net</a>. On that site, enter a place name or click on the map. The latitude and longitude appear as two numbers separated by a comma. The left number is latitude, the right number is longitude. If either number has a negative sign, don't delete it -- the negative sign is important for that number so that your pin gets placed in the right spot on the map.</p>
			</div>
		        <form method="post">
	                	<p style="font-weight: bold;">Store name</p>
	                	<input type="text" id="store-name" name="store-name"><br><br>
	                	<p style="font-weight: bold;">Description</p>
	                	<textarea cols="40" rows="6" id="description" name="description"></textarea><br><br>
				<p style="font-weight: bold;">Latitude</p>
				<input type="text" id="latitude" name="latitude"><br><br>
	                	<p style="font-weight: bold;">Longitude</p>
	                	<input type="text" id="longitude" name="longitude"><br><br>
	        	        <input type="submit" id="submit" name="submit">
			</form>
			<br><br>
EOT;
		} else if ( getCurrentUserId() != 0 ) {
	                $creator = getUsernameById( $r[ 0 ][ "uid" ] );
	                $name = $r[ 0 ][ "name" ];
	                $description = $r[ 0 ][ "descr" ];
	                $latlong = $r[ 0 ][ "latlong" ];
	                $o .=  <<< EOT
	                <h3>$name</h3>
	                <p id="description">$description</p>
	                <p>Created by <a href="$base/profile/$creator">$creator</a></p>
	                <p>Located at $latlong</p>
	                <h3>Products</h3>
*/
		if ( getCurrentUserId() != 0 ) {
			$pagedesc = "You do not yet have any products";
	                $r = q( "SELECT * FROM products WHERE uid = $uid" );
	                if ( DBA::isResult( $r ) ) {
	                        $json = json_encode( $r );
	                        $pagedesc = "Select a product for more info";
	                }
			foreach( $r as $datum ) {
				$userproducts[ $datum[ "id" ] ] = getUsernameById( $datum[ "uid" ] );
			}
			$userproducts = json_encode( $userproducts );

		        $o .=  <<< EOT

		        <h3>My store</h3>
		        <p>$pagedesc</p>
			<div align="right"><a href="/marketplace/new-product/"><button class="btn btn-default newbtn">New product</button></a></div>
			<script>
				var style = document.createElement( "style" );
				var css = document.createTextNode( ".newbtn {background-color: #fefefe;}" );
				style.appendChild( css );
				document.getElementsByTagName( "head" )[ 0 ].appendChild( style );
			</script>
		        <div id="products"></div>

			<br><br>

		        <script>
				var products = $json;
				products.sort(function(a, b){return b[ "id" ] - a[ "id" ]});
				var userproducts = $userproducts;
				function htmlDecode( input ){
					var e = document.createElement('div');
					e.innerHTML = input;
					return e.childNodes[0].nodeValue;
				}
		                function addProduct( name, description, details, profile, cats, price, latlng ) {
					cats = cats.split( ", " );
					var categories = "";
					var i; for ( i=0; i<cats.length; i++ ) {
						categories += '<div class="category" style="background-color: #ddd; padding: 3px; padding-left: 6px; padding-right: 6px; border-radius: 10px; display: inline-block; margin-left: 5px; max-width: 150px; overflow: hidden; text-overflow: ellipsis;">'
							+ '<span class="xcat" style="cursor: pointer;">&times;</span>&nbsp;' + '<span class="pluscat" style="cursor: pointer;">' + cats[ i ] + '</span>'
						+ '</div>';
					}
					price = "$" + ( price / 100 ).toFixed( 2 ) + " (USD)";
		                        document.getElementById( "products" ).innerHTML +=
					'<div class="prodbox" style="position: relative; border-radius: 10px; padding: 10px; background-color: white; box-shadow: 2px 2px 5px black; margin-top: 5px; margin-bottom: 5px;">'
						+ '<span class="prodname" style="font-weight: bold;">' + name + '</span>'
						+ '<div class="catbox" align="right" style="float: right; max-width: 150px;">'
							+ categories
						+ '</div>'
						+ '<div class="proddesc">' + htmlDecode( description ) + '</div>'
						+ '<div class="location">Located at ' + latlng + '</div>'
						+ '<div class="prodprice" style="position: absolute; bottom: 10px;">Price: ' + price + '</div>'
						+ '<hr class="separator" style="width: 100%; visibility: hidden;" />'
						+ '<div class="prodbtnbox" align="right">'
							+ '<a class="prodbtn" href="' + details + '">Details</a> | '
							+ '<a class="prodbtn" href="' + profile + '">Contact</a>'
						+ '</div>'
					+ '</div>';
		                }
				products.forEach( function( item ) {
					addProduct( item[ "name" ], item[ "descr" ], "$base/marketplace/details/?product=" + item[ "hash" ], "$base/profile/" + userproducts[ item[ "id" ] ], item[ "cat" ], item[ "price" ], item[ "latlong" ] );
				});
		        </script>
			<script type="text/javascript">
				var style = document.createElement( "style" );
				var css = document.createTextNode( ".proddesc p {margin: 0px;}" );
				style.appendChild( css );
				document.getElementsByTagName( "head" )[ 0 ].appendChild( style );
			</script>
			<script>
				var i; for ( i=0; i<document.getElementsByClassName( "xcat" ).length; i++ ) {
					document.getElementsByClassName( "xcat" )[ i ].addEventListener( "click", function() {
						sessionStorage[ "filter" ] = this.nextElementSibling.innerText;
						var j; for ( j=0; j<document.getElementsByClassName( "catbox" ).length; j++ ) {
							if ( document.getElementsByClassName( "catbox" )[ j ].innerText.includes( sessionStorage[ "filter" ] ) ) {
								document.getElementsByClassName( "catbox" )[ j ].parentElement.style.display = "none";
							}
						}
					});
				}
                                var i; for ( i=0; i<document.getElementsByClassName( "pluscat" ).length; i++ ) {
                                        document.getElementsByClassName( "pluscat" )[ i ].addEventListener( "click", function() {
                                                sessionStorage[ "filter" ] = this.innerText;
                                                var j; for ( j=0; j<document.getElementsByClassName( "catbox" ).length; j++ ) {
                                                        if ( !document.getElementsByClassName( "catbox" )[ j ].innerText.includes( sessionStorage[ "filter" ] ) ) {
                                                                document.getElementsByClassName( "catbox" )[ j ].parentElement.style.display = "none";
                                                        }
                                                }
                                        });
                                }
			</script>

EOT;
		} else {
                        $o .=  <<< EOT
                        <h3>Sorry!</h3>
                        <p>Please <a href="$base/register/">register</a> before creating a store.</p>
EOT;
		}
	}

        if ( strpos( $_SERVER['REQUEST_URI'], '/wallet/' ) > -1 ) {
                $uid = getCurrentUserId();
                $r = q( "SELECT * FROM wallets WHERE uid = $uid" );
/*		$lnbits_url = "https://s.lnbits.com";
		$user_id = "3e97e7f59e9d46ac89f73e98dc620bda";
		$wallet_name = "testwallet";
		$admin_id = "fb783e7cddbe425f8b23cd4861714ed2";
		$lnbits_apikey = "3cb786cd637942159fb37701cf9d02e9";
		$newWallet = generateLNBitsWallet( $lnbits_url, $user_id, $wallet_name, $admin_id, $lnbits_apikey );
*/
//		$username = "testman";
//		$lnbits_apikey = "3cb786cd637942159fb37701cf9d02e9";
//		$newuser = generateLNBitsUser( $username, $lnbits_apikey );
                if ( DBA::isResult( $r ) ) {
                        $wallet = $r[ 0 ];
                        $wdata = json_encode( $wallet );
			$domain = $wallet[ "lnbits-domain" ];
			$usr = $wallet[ "lnbits-user" ];
			$wal = $wallet[ "lnbits-wallet" ];
                }
                $o .=  <<< EOT
                <h3>Wallet</h3>
                <iframe height="800px" width="100%" allow="clipboard-read; clipboard-write;" src="$domain/wallet?usr=$usr&wal=$wal"></iframe>
EOT;
        }

        if ( strpos( $_SERVER['REQUEST_URI'], '/new-product/' ) > -1 ) {
                $uid = getCurrentUserId();
                $r = q( "SELECT id FROM products WHERE uid = $uid" );
                if ( DBA::isResult( $r ) ) {
                        $products = $r;
			$pdata = json_encode( $products );
                }
                $s = q( "SELECT id FROM wallets WHERE uid = $uid" );
//		$s = q( "SELECT * FROM wallets" );
                if ( DBA::isResult( $s ) ) {
                        $wallet = $s;
                        $wdata = json_encode( $wallet );
                }
		if ( getCurrentUserId() == 0 ) {
                        $o .=  <<< EOT
                        <h3>Sorry!</h3>
                        <p>Please <a href="$base/register/">register</a> before creating a product.</p>
EOT;
                }
                if ( getCurrentUserId() != 0 && $_POST[ "product-name" ] && $_POST[ "description" ] && $_POST[ "price" ] && isset( $_GET[ "processing" ] ) ) {
			$rand1 = rand( 1000000, 9999999 );
			$rand2 = rand( 1000000, 9999999 );
			$rand3 = rand( 1000000, 9999999 );
			$rand4 = rand( 1000000, 9999999 );
			$rand5 = rand( 1000000, 9999999 );
			$rand6 = rand( 1000000, 9999999 );
			$rand7 = rand( 1000000, 9999999 );
			$rand8 = rand( 1000000, 9999999 );
			$rand9 = rand( 1000000, 9999999 );
			$preimg = $rand1 . $rand2 . $rand3 . $rand4 . $rand5 . $rand6 . $rand7 . $rand8 . $rand9;
			$hash = hash( "sha256", $preimg );
                        $productname = $_POST[ "product-name" ];
                        $description = $_POST[ "description" ];
			$categories = $_POST[ "categories" ];
                        $price = $_POST[ "price" ];
			if ( $_POST[ "latitude" ] && $_POST[ "longitude" ] ) {
				$latlong = $_POST[ "latitude" ] . "," . $_POST[ "longitude" ];
			} else {
				$latlong = "";
			}
			if ( $_POST[ "sid" ] ) {
	                        $sid = $_POST[ "sid" ];
			} else {
				$sid = "";
			}
			if ( $_POST[ "buynow" ] == "on" ) {
                                $buynow = 1;
                        } else {
				$buynow = "";
			}
                        createProduct( $productname, $description, $categories, $price, $latlong, $sid, $buynow, $uid, $hash );
			createWallet( $uid );
			$o .=  <<< EOT
				<h3>Processing</h3>
				<p>Please be patient while we process your submission. You will be redirected to your new product shortly.</p>
				<script>
					setTimeout( function() { window.location.href = "$base/marketplace/details/?product=$hash" }, 2000 );
				</script>
EOT;
                }
		if ( isset( $_GET[ "processing" ] ) && ( getCurrentUserId() == 0 || !$_POST[ "product-name" ] || !$_POST[ "description" ] || !$_POST[ "price" ] ) ) {
			$o .= <<< EOT
				<h3>Oops!</h3>
				<p>There was an error with your submission, <a href="$base/marketplace/new-product/">please try again</a></p>
EOT;
		}
                if ( getCurrentUserId() != 0 && !isset( $_GET[ "processing" ] ) ) {
                        $o .=  <<< EOT
                        <h3>Add a product</h3>
                        <div id="instructions" style="padding: 10px; border: 3px solid black;">
                                <p style="font-weight: bold;">Instructions</p>
                                <p>Enter a name, description, categories, and price for your product. You'll also need a latitude and a longitude which help us place a pin on our map.</p>
                        </div>
			<!-- <p>$pdata</p>
			<p>$wdata</p> -->
			<br>
                        <form method="post" action="/marketplace/new-product/?processing=true">
                                <p style="font-weight: bold;">Product name</p>
                                <input type="text" id="product-name" name="product-name"><br><br>
                                <p style="font-weight: bold;">Description</p>
                                <textarea cols="40" rows="6" id="description" name="description"></textarea><br><br>
                                <p><strong>Categories</strong> (separated by a comma and a space, e.g. "Automobiles, trucks, Ford")</p>
                                <input type="text" id="categories" name="categories"><br><br>
                                <p style="font-weight: bold;">Price</p>
                                $ <input type="number" step="0.01" id="publicprice" name="publicprice"> (USD)<br><br>
                                <input type="hidden" id="price" name="price">
                                <input type="checkbox" id="buynow" name="buynow"> <label for="buynow">Buy now?</label>
				<p>If you let people buy now, they can immediately send you a bitcoin payment to buy your product. Otherwise, they can contact you to work out the details.</p>
				<p style="font-weight: bold;">Location</p>
				<button type="button" onclick="getLocation();">Get location</button><br><br>
				<script>
				        function getLocation() {
				                if ( navigator.geolocation ) {
				                        navigator.geolocation.getCurrentPosition( showPosition );
				                } else {
				                        console.log( "Geolocation is not supported by this browser." );
				                }
				        }
				        function showPosition( position ) {
						document.getElementById( "latitude" ).value = position.coords.latitude;
						document.getElementById( "longitude" ).value = position.coords.longitude;
				        	console.log( "Latitude: " + position.coords.latitude + ", Longitude: " + position.coords.longitude );
				        }
				</script>
				<div style="padding: 10px; border: 3px solid black;">
					<p>Get your latitude and longitude by clicking the Get Location button. Alternatively, go to <a href="https://latlong.net/" target="_blank">latlong.net</a>. On that site, enter a place name or click on the map. The latitude and longitude appear as two numbers separated by a comma. The left number is latitude, the right number is longitude. If either number has a negative sign, don't delete it -- the negative sign is important for that number so that your pin gets placed in the right spot on the map.</p>
				</div>
				<br>
                                <p style="font-weight: bold;">Latitude</p>
                                <input type="text" id="latitude" name="latitude"><br><br>
                                <p style="font-weight: bold;">Longitude</p>
                                <input type="text" id="longitude" name="longitude"><br><br>
                                <input type="submit" id="submit" name="submit">
                        </form>
                        <br><br>
			<script>
				$("#publicprice").on( "keyup", function(){
					document.getElementById( "price" ).value = Number( document.getElementById( "publicprice" ).value ).toFixed( 2 ) * 100;
				});
			</script>
			<script src="addon/marketplace/js/ckeditor.js"></script>
			<script>
			    ClassicEditor
			        .create( document.querySelector( '#description' ) )
			        .catch( error => {
			            console.error( error );
			        } );
			    $(document).ready(function(){
			        document.getElementsByClassName( "ck-editor__editable" )[ 0 ].style.height = "300px";
				$( ".ck-editor__editable" ).on( "focus", function() {
					document.getElementsByClassName( "ck-editor__editable" )[ 0 ].style.height = "300px";
				});
				$( ".ck-editor__editable" ).blur( function() {
					setTimeout( function() {
						document.getElementsByClassName( "ck-editor__editable" )[ 0 ].style.height = "300px";
					}, 5 );
				});
			    });
			</script>
			<script type="text/javascript">
				$(document).ready(function(){
					var i; for ( i=0; i<document.getElementsByClassName( "ck-tooltip__text" ).length; i++ ) {
					        if ( document.getElementsByClassName( "ck-tooltip__text" )[ i ].innerHTML == "Insert image" || document.getElementsByClassName( "ck-tooltip__text" )[ i ].innerHTML == "Insert media" ) {
					                document.getElementsByClassName( "ck-tooltip__text" )[ i ].parentElement.parentElement.style.display = "none";
					        }
					}
				});
			</script>
EOT;
                }
        }

        if ( strpos( $_SERVER['REQUEST_URI'], '/map/' ) > -1 ) {
                $r = q( "SELECT * FROM products" );
                if ( DBA::isResult( $r ) ) {
                        $json = json_encode( $r );
                }
                foreach( $r as $datum ) {
                        $userproducts[ $datum[ "id" ] ] = getUsernameById( $datum[ "uid" ] );
                }
                $userproducts = json_encode( $userproducts );

                $o .=  <<< EOT

                <h3>Products</h3>
                <p>Select a product using the map for more info</p>

                <div id="mapid"></div>

                <br><br>

                <script>
                        var lat = 0;
                        var lng = 0;
                        var zm = 1.4;
                        var mymap = L.map('mapid').setView([lat, lng], zm);
                        L.tileLayer('https://api.maptiler.com/maps/streets/{z}/{x}/{y}.png?key=LdwrABRv6rLocfqVmk9r',{
                                tileSize: 512,
                                zoomOffset: -1,
                                minZoom: 1,
	                        attribution: '<a href="https://www.maptiler.com/copyright/" target="_blank" rel="noopener noreferrer">© MapTiler</a> <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener noreferrer">© OpenStreetMap contributors</a>',
                                crossOrigin: true
                        }).addTo(mymap);
                        var redIcon = new L.Icon({
                                iconUrl: '$base/addon/marketplace/images/marker-icon-red.png',
                                shadowUrl: '$base/addon/marketplace/images/marker-shadow.png',
                                iconSize: [25, 41],
                                iconAnchor: [12, 41],
                                popupAnchor: [1, -34],
                                shadowSize: [41, 41]
                        });
                </script>

                <script>
                        var products = $json;
                        var userproducts = $userproducts;
                        function addPin( latlng, name, description, details, profile ) {
                                var lat = latlng.split( ',' )[ 0 ];
                                var lng = latlng.split( ',' )[ 1 ];
                                var iconColor = redIcon;
	                        L.marker( [ lat, lng ], { icon: redIcon } ).addTo( mymap ).bindPopup( '<span style="font-weight: bold;">' + name + '</span><br /><div class="description">' + description + '</div><hr style="width: 100%; visibility: hidden;" /><a href="' + details + '">Details</a> | <a href="' + profile + '">Contact</a>' );
                        }
			function htmlDecode(input){
				var e = document.createElement('div');
				e.innerHTML = input;
				return e.childNodes[0].nodeValue;
			}
                        products.forEach( function( item ) {
				addPin( item[ "latlong" ], item[ "name" ], htmlDecode( item[ "descr" ] ), "$base/marketplace/details/?product=" + item[ "hash" ], "$base/profile/" + userproducts[ item[ "id" ] ] );
                        });
                </script>

		<script type="text/javascript">
			var style = document.createElement( "style" );
			var css = document.createTextNode( ".description p {margin: 0px;}" );
			style.appendChild( css );
			document.getElementsByTagName( "head" )[ 0 ].appendChild( style );
		</script>
EOT;
	}

        //Don't delete this next line, it shows me how to do a negative constrait -- useful if I want something to show up on some but not all pages
        //if ( !( strpos( $_SERVER['REQUEST_URI'], '/details/' ) > -1 ) ) {
	if ( $o == '' ) {
		$pagedesc = 'There are not currently any products, <a href="/marketplace/new-product/">create one here</a>';
                $r = q( "SELECT * FROM products" );
                if ( DBA::isResult( $r ) ) {
                        $json = json_encode( $r );
			$pagedesc = "Select a product for more info";
                }
		foreach( $r as $datum ) {
			$userproducts[ $datum[ "id" ] ] = getUsernameById( $datum[ "uid" ] );
		}
		$userproducts = json_encode( $userproducts );

	        $o .=  <<< EOT

	        <h3>Products</h3>
	        <p>$pagedesc</p>

	        <div id="products"></div>
		<br><br>
		<script>
			var products = $json;
			products.sort(function(a, b){return b[ "id" ] - a[ "id" ]});
			var userproducts = $userproducts;
			var categories = [];
			products.forEach( function( item ) {
				cats = item[ "cat" ].split( ", " );
				var i; for ( i=0; i<cats.length; i++ ) {
					if ( categories.indexOf( cats[ i ] ) < 0 ) {
						categories.push( cats[ i ] );
					}
				}
			});
			function htmlDecode(input){
				var e = document.createElement('div');
				e.innerHTML = input;
				return e.childNodes[0].nodeValue;
			}
	                function addProduct( name, description, details, profile, cats, price, latlng ) {
				cats = cats.split( ", " );
				var categories = "";
				var i; for ( i=0; i<cats.length; i++ ) {
					categories += '<div class="category" style="background-color: #ddd; padding: 3px; padding-left: 6px; padding-right: 6px; border-radius: 10px; display: inline-block; margin-left: 5px; max-width: 150px; overflow: hidden; text-overflow: ellipsis;">'
						+ '<span class="xcat" style="cursor: pointer;">&times;</span>&nbsp;' + '<span class="pluscat" style="cursor: pointer;">' + cats[ i ] + '</span>'
					+ '</div>';
				}
                                price = "$" + ( price / 100 ).toFixed( 2 ) + " (USD)";
	                        document.getElementById( "products" ).innerHTML +=
				'<div class="prodbox" style="position: relative; border-radius: 10px; padding: 10px; background-color: white; box-shadow: 2px 2px 5px black; margin-top: 5px; margin-bottom: 5px;">'
					+ '<span class="prodname" style="font-weight: bold;">' + name + '</span>'
					+ '<div class="catbox" align="right" style="float: right; max-width: 150px;">'
						+ categories
					+ '</div>'
					+ '<div class="proddesc">' + htmlDecode( description ) + '</div>'
					+ '<div class="location">Located at ' + latlng + '</div>'
                                        + '<div class="prodprice" style="position: absolute; bottom: 10px;">Price: ' + price + '</div>'
					+ '<hr class="separator" style="width: 100%; visibility: hidden;" />'
					+ '<div class="prodbtnbox" align="right">'
						+ '<a class="prodbtn" href="' + details + '">Details</a> | '
						+ '<a class="prodbtn" href="' + profile + '">Contact</a>'
					+ '</div>'
				+ '</div>';
	                }
			products.forEach( function( item ) {
				addProduct( item[ "name" ], item[ "descr" ], "$base/marketplace/details/?product=" + item[ "hash" ], "$base/profile/" + userproducts[ item[ "id" ] ], item[ "cat" ], item[ "price" ], item[ "latlong" ] );
			});
	        </script>
		<script type="text/javascript">
			var style = document.createElement( "style" );
			var css = document.createTextNode( ".proddesc p {margin: 0px;}" );
			style.appendChild( css );
			document.getElementsByTagName( "head" )[ 0 ].appendChild( style );
		</script>
		<script>
			var i; for ( i=0; i<document.getElementsByClassName( "xcat" ).length; i++ ) {
				document.getElementsByClassName( "xcat" )[ i ].addEventListener( "click", function() {
					sessionStorage[ "filter" ] = this.nextElementSibling.innerText;
					var j; for ( j=0; j<document.getElementsByClassName( "catbox" ).length; j++ ) {
						if ( document.getElementsByClassName( "catbox" )[ j ].innerText.includes( sessionStorage[ "filter" ] ) ) {
							document.getElementsByClassName( "catbox" )[ j ].parentElement.style.display = "none";
						}
					}
				});
			}
                        var i; for ( i=0; i<document.getElementsByClassName( "pluscat" ).length; i++ ) {
                                document.getElementsByClassName( "pluscat" )[ i ].addEventListener( "click", function() {
                                        sessionStorage[ "filter" ] = this.innerText;
                                        var j; for ( j=0; j<document.getElementsByClassName( "catbox" ).length; j++ ) {
                                                if ( !document.getElementsByClassName( "catbox" )[ j ].innerText.includes( sessionStorage[ "filter" ] ) ) {
                                                        document.getElementsByClassName( "catbox" )[ j ].parentElement.style.display = "none";
                                                }
                                        }
                                });
                        }
		</script>

EOT;
	}

	return $o;
}

function makeMarketplaceTables() {
        if (DI::config()->get('retriever', 'dbversion') != '1.0') {
                $schema = file_get_contents(dirname(__file__).'/database.sql');
                $arr = explode(';', $schema);
                foreach ($arr as $a) {
                        $r = q($a);
                }
                DI::config()->set('marketplace', 'dbversion', '1.0');
        }
}

function getCurrentUserId() {
	$profileurl = Profile::getMyURL();
	$username = substr( $profileurl, strpos( $profileurl, 'profile' ) + 8 );
	$user = User::getByNickname( $username, array( "uid" ) );
	return $user[ "uid" ];
}

function getUsernameById( $id ) {
	$profileurl = User::getById( $id );
	$username = $profileurl[ "nickname" ];
	return $username;
}

function createProduct( $productname, $description, $categories, $price, $latlong = "", $sid = "", $buynow = "", $uid, $hash ) {
        $fields[ 'uid' ] = $uid;
        $fields[ 'name' ] = filter_var( $productname, FILTER_SANITIZE_STRING );
        $fields[ 'descr' ] = filter_var( $description, FILTER_SANITIZE_SPECIAL_CHARS );
        $fields[ 'cat' ] = filter_var( $categories, FILTER_SANITIZE_STRING );
        $fields[ 'price' ] = filter_var( $price, FILTER_SANITIZE_STRING );
        $fields[ 'latlong' ] = filter_var( $latlong, FILTER_SANITIZE_STRING );
        $fields[ 'status' ] = 1;
	$fields[ 'hash' ] = $hash;
        if ( !empty( $sid ) ) {
        	$fields[ 'sid' ] = $sid;
        }
        if ( !empty( $buynow ) ) {
        	$fields[ 'buynow' ] = 1;
        } else {
        	$fields[ 'buynow' ] = 0;
        }
        DBA::insert( 'products', $fields );
}

function createWallet( $uid ) {
        $r = q( "SELECT * FROM wallets WHERE uid = $uid" );
        if ( DBA::isResult( $r ) ) {
                $wallet = $r;
        }
	$lnbitsdata = q( "SELECT * FROM lnbitsadmin" );
	if ( DBA::isResult( $lnbitsdata ) ) {
		$lnbitsdata = json_encode( $lnbitsdata );
	}
	$field = "lnbits-domain";
	$field2 = "lnbits-admin-key";
	$lnbits_url = json_decode( $lnbitsdata )[ 0 ]->$field;
	$lnbits_apikey = json_decode( $lnbitsdata )[ 0 ]->$field2;
	if ( empty( $wallet ) ) {
		$username = getUsernameById( getCurrentUserId() );
		$user_id = json_decode( generateLNBitsUser( $username, $lnbits_apikey ) )->id;
		$users_wallet = findWallet( $user_id, $lnbits_apikey );
		$lnbits_api_key = json_decode( $users_wallet )[ 0 ]->adminkey;
		$lnbits_user = json_decode( $users_wallet )[ 0 ]->user;
		$lnbits_wallet = json_decode( $users_wallet )[ 0 ]->id;
		$lnbits_admin = json_decode( $users_wallet )[ 0 ]->admin;
		$lnbits_inv_key = json_decode( $users_wallet )[ 0 ]->inkey;
		$fields[ 'uid' ] = $uid;
        	$fields[ 'lnbits-domain' ] = $lnbits_url;
        	$fields[ 'lnbits-api-key' ] = $lnbits_api_key;
		$fields[ 'lnbits-inv-key' ] = $lnbits_inv_key;
        	$fields[ 'lnbits-user' ] = $lnbits_user;
        	$fields[ 'lnbits-wallet' ] = $lnbits_wallet;
        	DBA::insert( 'wallets', $fields );
	}
}

function generateLNBitsUser( $username, $lnbits_apikey ) {
	$lnbitsdata = q( "SELECT * FROM lnbitsadmin" );
	if ( DBA::isResult( $lnbitsdata ) ) {
		$lnbitsdata = json_encode( $lnbitsdata );
	}
	$field = "lnbits-domain";
	$field2 = "lnbits-admin-key";
	$field3 = "lnbits-admin-user";
	$lnbits_url = json_decode( $lnbitsdata )[ 0 ]->$field;
	$lnbits_apikey = json_decode( $lnbitsdata )[ 0 ]->$field2;
	$admin_id = json_decode( $lnbitsdata )[ 0 ]->$field3;
        $wallet_name = $username . "'s wallet";
	ob_start();
        $payload = '{"admin_id": "' . $admin_id . '", "wallet_name": "' . $wallet_name . '", "user_name": "' . $username . '"}';
        $url = $lnbits_url . '/usermanager/api/v1/users';
        $ch = curl_init();
        curl_setopt( $ch, CURLOPT_URL, $url );
        curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
                'X-Api-Key: ' . $lnbits_apikey,
                'Content-Type: application/json'
        ));
        curl_setopt( $ch, CURLOPT_POSTFIELDS, $payload );
        $head = curl_exec( $ch );
        $httpCode = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
        curl_close( $ch );
        $data = ob_get_clean();
        return $data;
}

function findWallet( $user_id, $lnbits_apikey ) {
	$lnbitsdata = q( "SELECT * FROM lnbitsadmin" );
	if ( DBA::isResult( $lnbitsdata ) ) {
		$lnbitsdata = json_encode( $lnbitsdata );
	}
	$field = "lnbits-domain";
	$lnbits_url = json_decode( $lnbitsdata )[ 0 ]->$field;
	ob_start();
	$url = $lnbits_url . '/usermanager/api/v1/wallets/' . $user_id;
	$ch = curl_init();
	curl_setopt( $ch, CURLOPT_URL, $url );
	curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
		'X-Api-Key: ' . $lnbits_apikey,
		'Content-Type: application/json'
	));
	$head = curl_exec( $ch );
	$httpCode = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
	curl_close( $ch );
	$data = ob_get_clean();
	return $data;
}

function generateLNBitsWallet( $lnbits_url, $user_id, $wallet_name, $admin_id, $lnbits_apikey ) {
        ob_start();
        $payload = '{"user_id": "' . $user_id . '", "wallet_name": "' . $wallet_name . '", "admin_id": "' . $admin_id . '"}';
        $url = $lnbits_url . '/usermanager/api/v1/wallets';
        $ch = curl_init();
        curl_setopt( $ch, CURLOPT_URL, $url );
        curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
                'X-Api-Key: ' . $lnbits_apikey,
                'Content-Type: application/json'
        ));
        curl_setopt( $ch, CURLOPT_POSTFIELDS, $payload );
        $head = curl_exec( $ch );
        $httpCode = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
        curl_close( $ch );
        $data = ob_get_clean();
        return $data;
}

function requestInvoice( $lnbits_url, $amount, $memo, $lnbits_apikey, $webhook = "" ) {
	if ( empty( $webhook ) ){
	        $payload = '{"out": false, "amount": ' . $amount . ', "memo": "' . $memo . '"}';
	} else {
	        $payload = '{"out": false, "amount": ' . $amount . ', "memo": "' . $memo . '", "webhook": "' . $webhook . '"}';
	}
        ob_start();
	$url = $lnbits_url . '/api/v1/payments';
        $ch = curl_init();
        curl_setopt( $ch, CURLOPT_URL, $url );
        curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
                'X-Api-Key: ' . $lnbits_apikey,
                'Content-Type: application/json'
        ));
        curl_setopt( $ch, CURLOPT_POSTFIELDS, $payload );
        $head = curl_exec( $ch );
        $httpCode = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
        curl_close( $ch );
        $data = ob_get_clean();
        $data = json_decode( $data, true );
        $result["invoice"] = $data["payment_request"];
        $result["pmthash"] = $data["payment_hash"];
        $json = json_encode( $result );
        return $json;
}

function showInvoice( $lnbits_url, $invoice, $invkey, $product ) {
	$script = '
		<div id="lnurl-auth-qr" align="center" style="max-width: 200px; margin: auto; display: none;"><a id="lnurl-auth-link" target="_blank"></a></div>
		<script src="' . $base . '/addon/marketplace/js/qrcode.js"></script>
		<script>
			var invoice = [' . $invoice . '];
			function createQR( data ) {
                                var dataUriPngImage = document.createElement( "img" ),
                                s = QRCode.generatePNG( data, {
                                        ecclevel: "M",
                                        format: "html",
                                        fillcolor: "#FFFFFF",
                                        textcolor: "#373737",
                                        margin: 4,
                                        modulesize: 8
                                } );
                                dataUriPngImage.src = s;
                                dataUriPngImage.id = "lnurl-auth-image";
				dataUriPngImage.style = "width: 100%;";
				return dataUriPngImage;
                        }
			document.getElementById( "lnurl-auth-link" ).appendChild( createQR( invoice[ 0 ].invoice.toUpperCase() ) );
			document.getElementById( "lnurl-auth-link" ).href = "lightning:" + invoice[ 0 ].invoice;
			var caption = document.createElement( "pre" );
			caption.id = "lnurl-auth-caption";
			caption.style = "width: 100%; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; border: 1px solid black; padding: 5px;";
			caption.innerText = invoice[ 0 ].invoice;
			document.getElementById( "lnurl-auth-qr" ).append( caption );
			var pmthash = invoice[ 0 ].pmthash;
			function checkPmtStatus( pmthash ) {
				var url = "' . $base . '/addon/marketplace/marketplace.php?action=checkpmtstatus&pmthash=" + pmthash + "&invkey=' . $invkey . '&lnbitsurl=' . $lnbits_url . '";
                		var xhttp = new XMLHttpRequest();
                		xhttp.onreadystatechange = function() {
                	        	if ( this.readyState == 4 && this.status == 200 ) {
						var receipt = "' . $base . '/marketplace/receipt/?product=' . $product . '&pmthash=" + pmthash + "&lnbitsurl=' . $lnbits_url . '";
                	                	if ( this.responseText == 1 ) {
							window.location.href = receipt;
	                                	} else {
	                        	                setTimeout( function() {checkPmtStatus( pmthash );}, 1000 );
	                	                }
	        	                }
		                };
	                	xhttp.open( "GET", url, true );
	        	        xhttp.send();
		        }
			checkPmtStatus( pmthash );
		</script>
	';
	return $script;
}

function usdToSats( $amount ) {
	ob_start();
	$url = "https://api.coinbase.com/v2/prices/BTC-USD/spot";
	$ch = curl_init();
	curl_setopt( $ch, CURLOPT_URL, $url );
	curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
		'Content-Type: application/json'
	));
	$head = curl_exec( $ch );
	$httpCode = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
	curl_close( $ch );
	$data = ob_get_clean();
	$curl = json_decode( $data );
	$price_of_btc = $curl->data->amount;
	$amt_in_btc = ( $amount / 100 ) / $price_of_btc;
	$amt_in_btc = $amt_in_btc * 100000000;
	$amt_in_btc = round( $amt_in_btc );
	return $amt_in_btc;
}

function checkpmtstatus( $opt_hash = "", $opt_key = "", $opt_url = "" ) {
	if ( empty( $opt_hash ) ) {
		$pmthash = $_GET[ "pmthash" ];
	} else {
		$pmthash = $opt_hash;
	}
	if ( empty( $opt_key ) ) {
		$invkey = $_GET[ "invkey" ];
	} else {
		$invkey = $opt_key;
	}
	if ( empty( $opt_url ) ) {
		$lnbits_url = $_GET[ "lnbitsurl" ];
	} else {
		$lnbits_url = $opt_url;
	}
        ob_start();
        $url = $lnbits_url . '/api/v1/payments/' . $pmthash;;
        $ch = curl_init();
        curl_setopt( $ch, CURLOPT_URL, $url );
        curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
                'X-Api-Key: ' . $invkey,
                'Content-Type: application/json'
        ));
        $head = curl_exec( $ch );
        $httpCode = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
        curl_close( $ch );
        $paychecker = ob_get_clean();
        if ( $paychecker == 1 ) {$paychecker = "true";}
        if ( strpos( $paychecker, "true" ) !== false ) {
            $paychecker = 1;
        } else {
            $paychecker = 0;
        }
        return $paychecker;
}

if ( $_GET[ "action" ] == "checkpmtstatus" ) {
	echo checkpmtstatus();
}
