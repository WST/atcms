// © Vasily V. Thriller, 2007
// © Ilja "the1st" Averkov, 2007
// All rights reserved. For using by the HACSoft group only

function fetch_object(idname){
  if (document.getElementById)  {
    return document.getElementById(idname);
  }else if (document.all){
    return document.all[idname];
  }else if (document.layers){
    return document.layers[idname];
  }else{
    return null;
  }
}

function insertTags(object, tagOpen, tagClose, sampleText){
  var txtarea = fetch_object(object);
  if(document.selection){
    //IE
    var theSelection = document.selection.createRange().text;
    if (!theSelection)
      theSelection=sampleText;
    txtarea.focus();
    if(theSelection.charAt(theSelection.length - 1) == " "){ // exclude ending space char, if any
      theSelection = theSelection.substring(0, theSelection.length - 1);
      document.selection.createRange().text = tagOpen + theSelection + tagClose + " ";
    }else{
      document.selection.createRange().text = tagOpen + theSelection + tagClose;
    }
  }else if(txtarea.selectionStart || txtarea.selectionStart == '0'){
    //Gecko
    var replaced = false;
    var startPos = txtarea.selectionStart;
    var endPos = txtarea.selectionEnd;
    if(endPos-startPos)
      replaced = true;
    var scrollTop = txtarea.scrollTop;
    var myText = (txtarea.value).substring(startPos, endPos);
    if(!myText)
      myText=sampleText;
    if(myText.charAt(myText.length - 1) == " "){
      subst = tagOpen + myText.substring(0, (myText.length - 1)) + tagClose + " ";
    }else{
      subst = tagOpen + myText + tagClose;
    }
    txtarea.value = txtarea.value.substring(0, startPos) + subst +
      txtarea.value.substring(endPos, txtarea.value.length);
    txtarea.focus();
    //set new selection
    if(replaced){
      var cPos = startPos+(tagOpen.length+myText.length+tagClose.length);
      txtarea.selectionStart = cPos;
      txtarea.selectionEnd = cPos;
    }else{
      txtarea.selectionStart = startPos+tagOpen.length;
      txtarea.selectionEnd = startPos+tagOpen.length+myText.length;
    }
    txtarea.scrollTop = scrollTop;
  }
  if (txtarea.createTextRange)
    txtarea.caretPos = document.selection.createRange().duplicate();
}

function addquote(name,id){
  m = fetch_object(id).innerText==null?fetch_object(id).textContent:fetch_object(id).innerText;
  insertTags('[quote='+name+']'+m+'[/quote]\n','','');
}

var targetobj = false;
var http_request = false;
var request_error = true;

function makeRequest(obj, url){
  http_request = false;
  targetobj = obj;
  if (window.XMLHttpRequest) { // Mozilla, Safari,...
    http_request = new XMLHttpRequest();
    if (http_request.overrideMimeType) {
      http_request.overrideMimeType('text/plain');
      // See note below about this line
    }
  } else if (window.ActiveXObject) { // IE
    try {
      http_request = new ActiveXObject("Msxml2.XMLHTTP");
    } catch (e) {
      try {
        http_request = new ActiveXObject("Microsoft.XMLHTTP");
      } catch (e) {}
    }
  }
  if (!http_request) {
    return true; //Ошибочка
  }
  http_request.onreadystatechange = alertContents;
  http_request.open('GET', url, true);
  http_request.send(null);
  //return request_error; Не работает
  return false;
}
function alertContents() {
  if (http_request.readyState == 4) {
    if (http_request.status == 200) {
      fetch_object(targetobj).innerHTML = http_request.responseText;
      request_error = false;
    } else {
      request_error = true;
    }
  }
}

function refresh_tree(tree_language, id_element, direction)
{
	return makeRequest('treecontainer', 'misc/jtree.php?l=' + tree_language + '&id_element=' + id_element + '&direction=' + direction);
}
