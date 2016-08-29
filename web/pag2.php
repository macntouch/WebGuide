<?php
require("header.php");
?>
<script type="text/javascript">


    //Variables Sip
    var oSipStack, onSipEventStack, onSipEventSession;
    var estat;
    var txtPhoneNumber;
    var txtDisplayName, txtPrivateIdentity, txtPublicIdentity, txtPassword, txtRealm;
    var txtRegStatus, txtCallStatus;

    var s_websocket_server_url;
    var s_sip_outboundproxy_url;
    
    var videoRemote, videoLocal, audioRemote;

    /*var sTransferNumber;
    var oRingTone, oRingbackTone;
    var btnCall, btnHangUp;
    var btnRegister, btnUnRegister;
    var btnFullScreen, btnHoldResume, btnTransfer, btnKeyPad;
    var videoRemote, videoLocal;
    var divVideo, divCallOptions;
    var tdVideo;
    var bFullScreen = false;
    var oNotifICall;
    var oReadyStateTimer;
    var bDisableVideo = false; */


    //Variables QR
    var gCtx = null;
    var gCanvas = null;
    var stype=0;
    var gUM=false;
    var webkit=false;
    var v=null;
    var vidhtml = '<video id="v" autoplay></video>';
        
    window.onload = function () {
         SIPml.init(postInit);
        
      initCanvas(800,600);
        qrcode.callback = read;

        txtPhoneNumber = document.getElementById("txtphonenumber");



      
      videoLocal = document.getElementById("video_local");
        videoRemote = document.getElementById("video_remote");
        audioRemote = document.getElementById("audio_remote");
	autoRegister();

    }

function postInit() {
        // check webrtc4all version
        if (SIPml.isWebRtc4AllSupported() && SIPml.isWebRtc4AllPluginOutdated()) {            
            if (confirm("Your WebRtc4all extension is outdated ("+SIPml.getWebRtc4AllVersion()+"). A new version with critical bug fix is available. Do you want to install it?\nIMPORTANT: You must restart your browser after the installation.")) {
                window.location = 'http://code.google.com/p/webrtc4all/downloads/list';
                return;
            }
        }

        // check for WebRTC support
        if (!SIPml.isWebRtcSupported()) {
            // is it chrome?
            if (SIPml.getNavigatorFriendlyName() == 'chrome') {
                    if (confirm("You're using an old Chrome version or WebRTC is not enabled.\nDo you want to see how to enable WebRTC?")) {
                        window.location = 'http://www.webrtc.org/running-the-demos';
                    }
                    else {
                        window.location = "index.html";
                    }
                    return;
            }
                
            // for now the plugins (WebRTC4all only works on Windows)
            if (SIPml.getSystemFriendlyName() == 'windows') {
                // Internet explorer
                if (SIPml.getNavigatorFriendlyName() == 'ie') {
                    // Check for IE version 
                    if (parseFloat(SIPml.getNavigatorVersion()) < 9.0) {
                        if (confirm("You are using an old IE version. You need at least version 9. Would you like to update IE?")) {
                            window.location = 'http://windows.microsoft.com/en-us/internet-explorer/products/ie/home';
                        }
                        else {
                            window.location = "index.html";
                        }
                    }

                    // check for WebRTC4all extension
                    if (!SIPml.isWebRtc4AllSupported()) {
                        if (confirm("webrtc4all extension is not installed. Do you want to install it?\nIMPORTANT: You must restart your browser after the installation.")) {
                            window.location = 'http://code.google.com/p/webrtc4all/downloads/list';
                        }
                        else {
                            // Must do nothing: give the user the chance to accept the extension
                            // window.location = "index.html";
                        }
                    }
                    // break page loading ('window.location' won't stop JS execution)
                    if (!SIPml.isWebRtc4AllSupported()) {
                        return;
                    }
                }
                else if (SIPml.getNavigatorFriendlyName() == "safari" || SIPml.getNavigatorFriendlyName() == "firefox" || SIPml.getNavigatorFriendlyName() == "opera") {
                    if (confirm("Your browser don't support WebRTC.\nDo you want to install WebRTC4all extension to enjoy audio/video calls?\nIMPORTANT: You must restart your browser after the installation.")) {
                        window.location = 'http://code.google.com/p/webrtc4all/downloads/list';
                    }
                    else {
                        window.location = "index.html";
                    }
                    return;
                }
            }
            // OSX, Unix, Android, iOS...
            else {
                if (confirm('WebRTC not supported on your browser.\nDo you want to download a WebRTC-capable browser?')) {
                    window.location = 'https://www.google.com/intl/en/chrome/browser/';
                }
                else {
                    window.location = "index.html";
                }
                return;
            }
        }

        // checks for WebSocket support
        if (!SIPml.isWebSocketSupported() && !SIPml.isWebRtc4AllSupported()) {
            if (confirm('Your browser don\'t support WebSockets.\nDo you want to download a WebSocket-capable browser?')) {
                window.location = 'https://www.google.com/intl/en/chrome/browser/';
            }
            else {
                window.location = "index.html";
            }
            return;
        }

        // FIXME: displays must be per session

        // attachs video displays
        if (SIPml.isWebRtc4AllSupported()) {
            viewVideoLocal = document.getElementById("divVideoLocal");
            viewVideoRemote = document.getElementById("divVideoRemote");
            WebRtc4all_SetDisplays(viewVideoLocal, viewVideoRemote); // FIXME: move to SIPml.* API
        }
        else{
            viewVideoLocal = videoLocal;
            viewVideoRemote = videoRemote;
        }

        if (!SIPml.isWebRtc4AllSupported() && !SIPml.isWebRtcSupported()) {
            if (confirm('Your browser don\'t support WebRTC.\naudio/video calls will be disabled.\nDo you want to download a WebRTC-capable browser?')) {
                window.location = 'https://www.google.com/intl/en/chrome/browser/';
            }
        }
        
       oConfigCall = {
            audio_remote: audioRemote,
            video_local: viewVideoLocal,
            video_remote: viewVideoRemote,
            bandwidth: { audio:undefined, video:undefined },
            video_size: { minWidth:undefined, minHeight:undefined, maxWidth:undefined, maxHeight:undefined },
            events_listener: { events: '*', listener: onSipEventSession },
            sip_caps: [
                            { name: '+g.oma.sip-im' },
                            { name: '+sip.ice' },
                            { name: 'language', value: '\"en,fr\"' }
                        ]
        };
    }
    
    //FUNCIONS QR--------------------------------------------------------------------------------------------------------------------

    function initCanvas(ww,hh)
    {
        gCanvas = document.getElementById("qr-canvas");
        var w = ww;
        var h = hh;
        gCanvas.style.width = w + "px";
        gCanvas.style.height = h + "px";
        gCanvas.width = w;
        gCanvas.height = h;
        gCtx = gCanvas.getContext("2d");
        gCtx.clearRect(0, 0, w, h);

    }


    function captureToCanvas() {
        if(stype!=1)
            return;
        if(gUM)
        {
            gCtx.drawImage(v,0,0);

            try{
                qrcode.decode();
            }
            catch(e){
                console.log(e);
                setTimeout(captureToCanvas, 500);
            };
        }

    }

    function read(a)
    {
        stype=0;
        console.log(qrcode.result);
        var tmp=qrcode.result;


        document.getElementById("outdiv").innerHTML ='<h1>Código reconocido</h1><p class="lead">En breves instantes recibiras el audio</p>';

	tsk_utils_log_info("Numero=" + (tmp || "(null)"));
        trucar(tmp);
    }


    function success(stream) {
        if(webkit)
            v.src = window.webkitURL.createObjectURL(stream);
        else
            v.src = stream;
        gUM=true;

        setTimeout(captureToCanvas, 500);
    }

    function error(error) {
        gUM=false;
        return;
    }


    function setwebcam()
    {


        if(stype==1)
        {
            setTimeout(captureToCanvas, 500);
            return;
        }

        var n=navigator;
        if(n.getUserMedia)
        {
            document.getElementById("outdiv").innerHTML = vidhtml;
            v=document.getElementById("v");
            n.getUserMedia({video: true, audio: false}, success, error);
        }
        else
        if(n.webkitGetUserMedia)
        {
            document.getElementById("outdiv").innerHTML = vidhtml;
            v=document.getElementById("v");
            webkit=true;
            n.webkitGetUserMedia({video: true, audio: false}, success, error);
        }
        else
        if(n.mozGetUserMedia)
        {
            document.getElementById("outdiv").innerHTML = vidhtml;
            v=document.getElementById("v");
            n.mozGetUserMedia({video: true, audio: false}, success, error);
        }
        stype=1;
        setTimeout(captureToCanvas, 500);
    }


    
     //FUNCIONS SIP--------------------------------------------------------------------------

    //FUNCIONS SIP--------------------------------------------------------------------------

    function autoRegister(){

        omplirdefecte();
        Register();
    }

    function omplirdefecte(){
        txtDisplayName="1060";
        txtPrivateIdentity="1060";
        txtPublicIdentity="sip:1060@192.168.56.101";
        txtPassword="1060";
        txtRealm="192.168.56.101";
        s_websocket_server_url="ws://192.168.56.101:8088/ws";
        s_sip_outboundproxy_url=null;

        tsk_utils_log_info("txtDisplayName=" + (txtDisplayName || "(null)"));
        tsk_utils_log_info("txtPrivateIdentity=" + (txtPrivateIdentity || "(null)"));
        tsk_utils_log_info("txtPublicIdentity=" + (txtPublicIdentity || "(null)"));
        tsk_utils_log_info("txtPassword=" + (txtPassword || "(null)"));
        tsk_utils_log_info("txtRealm=" + (txtRealm || "(null)"));
        tsk_utils_log_info("s_websocket_server_url=" + (s_websocket_server_url || "(null)"));
        
    }
function omplir (){
	txtDisplayName="<?php echo $_SESSION['nombre']; ?>";
        txtPrivateIdentity="<?php echo $_SESSION['nombre']; ?>";
        txtPublicIdentity="sip:<?php echo $_SESSION['nombre']; ?>@192.168.56.101";
        txtPassword="<?php echo $_SESSION['password']; ?>";
        txtRealm="192.168.56.101";
        s_websocket_server_url="ws://192.168.56.101:8088/ws";
        s_sip_outboundproxy_url=null;

        tsk_utils_log_info("txtDisplayName=" + (txtDisplayName || "(null)"));
        tsk_utils_log_info("txtPrivateIdentity=" + (txtPrivateIdentity || "(null)"));
        tsk_utils_log_info("txtPublicIdentity=" + (txtPublicIdentity || "(null)"));
        tsk_utils_log_info("txtPassword=" + (txtPassword || "(null)"));
        tsk_utils_log_info("txtRealm=" + (txtRealm || "(null)"));
        tsk_utils_log_info("s_websocket_server_url=" + (s_websocket_server_url || "(null)"));
}

    // sends SIP REGISTER request to login
    function Register(){

        // catch exception for IE (DOM not ready)
        try {// create SIP stack

            if (!txtRealm || !txtPrivateIdentity || !txtPublicIdentity) {

                return;
            }
            var o_impu = tsip_uri.prototype.Parse(txtPublicIdentity);
            if (!o_impu || !o_impu.s_user_name || !o_impu.s_host) {

                return;
            }

            // enable notifications if not already done
            if (window.webkitNotifications && window.webkitNotifications.checkPermission() != 0) {
                window.webkitNotifications.requestPermission();
            }

            var i_port;
            var s_proxy;

            if (!tsk_utils_have_websocket()) {
                // port and host will be updated using the result from DNS SRV(NAPTR(realm))
                i_port = 5060;
                s_proxy = txtRealm;

            }
            else {
                // there are at least 5 servers running on the cloud on ports: 4062, 5062, 6062, 7062 and 8062
                // we will connect to one of them and let the balancer to choose the right one (less connected sockets)
                // each port can accept up to 65K connections which means that the cloud can manage 325K active connections
                // the number of port will be increased or decreased based on the current trafic
                i_port = 4062 + (((new Date().getTime()) % 5) * 1000);
                s_proxy = "sipml5.org";

            }
          
          
            // create a new SIP stack. Not mandatory as it's possible to reuse the same satck
           oSipStack = new SIPml.Stack({
                    realm: txtRealm,
                    impi: txtPrivateIdentity,
                    impu: txtPublicIdentity,
                    password: txtPassword,
                    display_name: txtDisplayName,
                    websocket_proxy_url: s_websocket_server_url,
                    outbound_proxy_url: s_sip_outboundproxy_url,
                    ice_servers: null,
                    enable_rtcweb_breaker: true,
                    events_listener: { events: '*', listener: onSipEventStack },
                    sip_headers: [
                            { name: 'User-Agent', value: 'IM-client/OMA1.0 sipML5-v1.2013.04.26' },
                            { name: 'Organization', value: 'Doubango Telecom' }
                    ]
                }
            );


          

            if (oSipStack.start() != 0) {

            }
            else return;
        }
        catch (e) {

        }
    }

    // sends SIP REGISTER (expires=0) to logout
    function UnRegister() {
        if (oSipStack) {
            oSipStack.stop(); // shutdown all sessions
        }
    }

    function trucar(a){
        if(a==null){
            a=txtphonenumber.value;
        }
     
      
      var oConf = {
            audio_remote: audioRemote,
            video_local: viewVideoLocal,
            video_remote: viewVideoRemote,
            events_listener: { events: '*', listener: onSipEventSession },
            sip_caps: [
                            { name: '+g.oma.sip-im' },
                            { name: '+sip.ice' },
                            { name: 'language', value: '\"en,fr\"' }
                        ]
        };
	
      
      
        if (!tsk_string_is_null_or_empty(a)) {
            // create call session
            oSipSessionCall = oSipStack.newSession('call-audio', oConf);
            // make call
            if (oSipSessionCall.call(a) != 0) {
                oSipSessionCall = null;

                return;
            }
        }
      
         
        else {

        }
    }

    // Callback function for SIP Stacks
     function onSipEventStack(e /*SIPml.Stack.Event*/) {
        // this is a special event shared by all sessions and there is no "e_stack_type"
        // check the 'sip/stack' code
        tsk_utils_log_info('==stack event = ' + e.type);
        switch (e.type) {
            case 'started':
            {
                // catch exception for IE (DOM not ready)
                try {
                    // LogIn (REGISTER) as soon as the stack finish starting
                    oSipSessionRegister = this.newSession('register', {
                            expires: 200,
                            events_listener: { events: '*', listener: onSipEventSession },
                            sip_caps: [
                                        { name: '+g.oma.sip-im', value: null },
                                        { name: '+audio', value: null },
                                        { name: 'language', value: '\"en,fr\"' }
                                ]
                        });
                        oSipSessionRegister.register();
                    }
                    catch (e) {


                    }
                    break;
            }
               
            
            case 'stopping': case 'stopped': case 'failed_to_start': case 'failed_to_stop':
            {
                    var bFailure = (e.type == 'failed_to_start') || (e.type == 'failed_to_stop');
                oSipStack = null;
                oSipSessionRegister = null;
                oSipSessionCall = null;



                break;
            }

            case 'i_new_call': {break;}              
            case 'm_permission_requested':   {break;}             
            case 'm_permission_accepted':{break;}
            case 'm_permission_refused':   {break;}             
            case 'starting': default: break;
        
        }
    };
      
      
     // Callback function for SIP sessions (INVITE, REGISTER, MESSAGE...)
    function onSipEventSession(e /* SIPml.Session.Event */) {
        tsk_utils_log_info('==session event = ' + e.type);

        switch (e.type) {
            case 'connecting': case 'connected':
                {
                    var bConnected = (e.type == 'connected');
                    if (e.session == oSipSessionRegister) {
                        

                    }
                    else if (e.session == oSipSessionCall) {
                        
                        


                        
                        if (SIPml.isWebRtc4AllSupported()) { // IE don't provide stream callback
                            uiVideoDisplayEvent(true, true);
                            uiVideoDisplayEvent(false, true);
                        }
                    }
                    break;
                } // 'connecting' | 'connected'
                                   
            case 'terminating': case 'terminated':
                {
                    if (e.session == oSipSessionRegister) {
                        uiOnConnectionEvent(false, false);

                        oSipSessionCall = null;
                        oSipSessionRegister = null;


                    }
                    else if (e.session == oSipSessionCall) {
                        uiCallTerminated(e.description);
                    }
                    break;
                } // 'terminating' | 'terminated'
            
            case 'm_stream_video_local_added':{break;}                
            case 'm_stream_video_local_removed':{break;}                
            case 'm_stream_video_remote_added':{break;}                
            case 'm_stream_video_remote_removed':{break;}                
            case 'm_stream_audio_local_added':{break;}
            case 'm_stream_audio_local_removed':{break;}
            case 'm_stream_audio_remote_added':{break;}
            case 'm_stream_audio_remote_removed':{break;}
            case 'i_ect_new_call':{break;}    
            case 'i_ao_request':{break;}                
            case 'm_early_media':{break;}                
            case 'm_local_hold_ok':{break;}                
            case 'm_local_hold_nok': {break;}               
            case 'm_local_resume_ok': {break;}               
            case 'm_local_resume_nok': {break;}               
            case 'm_remote_hold':   {break;}            
            case 'm_remote_resume': {break;}               
            case 'o_ect_trying':     {break;}           
            case 'o_ect_accepted':  {break;}              
            case 'o_ect_completed':{break;}
            case 'i_ect_completed':  {break;}              
            case 'o_ect_failed':{break;}
            case 'i_ect_failed':   {break;}             
            case 'o_ect_notify':{break;}
            case 'i_ect_notify':   {break;}             
            case 'i_ect_requested':{break;}
                
        }
    };
      
      
      function uiCallTerminated(s_description){
        

        oSipSessionCall = null;

       


        
        


        location.href = "pag3.php";
    }
     
</script>

			<div id="QR" class="jumbotron">
							<div id="outdiv"></div>
							<canvas id="qr-canvas" width="800" height="600" hidden="hidden"></canvas>
 				<div id="buttonQR">
						<input value="START QR" onclick="setwebcam()" type="button" class="btn btn-primary btn-lg">
						<input value="STOP QR" onclick="ir(2)" type="button" class="btn btn-primary btn-lg">

				</div>								
							


							
							
							</div>
						
        <!-- Audios --> <audio id="audio_remote" autoplay="autoplay"></audio>
					<?php
require("footer.php");
?>	