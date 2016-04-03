/**
* © Вася Триллер
*/

function fetch_object(idname)
{
	if(document.getElementById)
	{
		return document.getElementById(idname);
	}
	else if(document.all)
	{
		return document.all[idname];
	}
	else if(document.layers)
	{
		return document.layers[idname];
	}
	else
	{
		return null;
	}
}

/**
* © WatchRooster
*/


function check_minlength(field, errmes, minlen)
{
	f = fetch_object(field);
	if(f.value.length < minlen)
	{
		alert(errmes);
		return false;
	}
	return true;
}

/**
* © Arigato
*/


function setCookie(name,value)
{
	var eD = new Date();
	eD.setTime(eD.getTime() + 31104000000);
	document.cookie = name + '=' + escape(value) + '; path=; expires=' + eD.toGMTString();
}

/**
* © Arigato
*/


function getCookie(name)
{
	var oC=document.cookie;
	if(!oC || oC == '') return '';
	oC = oC.split(';');
	var Ck;
	for(var i = 0; i < oC.length; i++)
	{
		Ck = oC[i].split('=')[0];
		if(Ck.charAt(0) == ' ') Ck = Ck.substring(1);
		if(Ck == name) 
		{
			var r = oC[i].split("=");
			if (r.length > 1) return unescape(r[1]);
			else return '';
		}
	}
	return '';
}

/**
* © Arigato & WatchRooster
*/


function switch_block(block_id)
{
	b = fetch_object(block_id);
	if (b.style.display == "none")
	{
		b.style.display = "block";
		setCookie(block_id, "1");
	}
	else
	{
		b.style.display="none";
		setCookie(block_id, "0");
	}
}

