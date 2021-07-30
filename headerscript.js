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
	}
	link0.href = "/marketplace/my-store/";
	tab0.innerHTML = "";
        tab0.append( link0 );
        if ( document.getElementById( "site-location" ).innerText.includes( "@" ) ) {
                document.getElementsByClassName( "tabs" )[ 0 ].append( tab0 );
        }
});
