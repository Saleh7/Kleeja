/** ------------------------------------------------
	^_^ JavaScript
	Filename: 	styles/default/javascript.js
	package:	Kleeja  copyright(c)2007-2013
	URL:		http://www.kleeja.com
	-------------------------------------------------
	$Id: javascript.js 2131 2013-02-19 12:25:57Z developer.mahmoud $
	-------------------------------------------------
**/

	$(document).ready(function(){
		
		//display new input until it exceed maximum input
		$('.file,.url').change(function(){
			var i = this.name.replace(/file|_/g,'');
			
			if( i >= number_of_uploads ){alert(LANG_MORE_F_FILES);return;};
			
			if(this.type == 'file') $('.file:eq('+ i +')').css('display', 'block');
			else $('.url:eq('+ i +')').css('display', 'block');
		});
		
		//display loader while upload files
		$('#uploader').submit(function(){
			$('#loadbox').css('display', 'block');
			$('#uploader').css('display', 'none');
		});


	});
	
	//
	//javascript for captcha
	//
	function update_kleeja_captcha(captcha_file, input_id)
	{
		document.getElementById(input_id).value = '';
		//Get a reference to CAPTCHA image
		img = document.getElementById('kleeja_img_captcha'); 
		 //Change the image
		img.src = captcha_file + '&' + Math.random();
	}

	//javascript for tabs
	function tabs(c1,c2,c3,c4){var m2,m3,l,t;var onclickf=function(){if(l<1)return 0;for(t=0;t<l;t++)if(m2[t]!=this){m2[t].className="";m3[t].style.display="none"}else{m2[t].className=c4;m3[t].style.display="block"}};var cl=function(){if(l<1)return 0;m2[0].className=c4;m3[0].style.display="block";for(t=0;t<l;t++)m2[t].onclick=onclickf};m2=$(c1+" "+c2+" li");if(m2[0]!="undefined"){m3=$(c1+" "+c3);l=m2.length;cl()}}$(document).ready(function(){var mah;mah=new tabs(".tabmain",".tabnav",".tabcon","tabactive")});
