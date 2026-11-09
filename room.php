<?php
require_once 'includes/functions.php';
$user = require_login();
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
$q = $pdo->prepare('SELECT * FROM game_rooms WHERE id = ?');
$q->execute([$id]);
$room = $q->fetch();
if (!$room) {
    http_response_code(404);
    exit('Room not found.');
}
$member = $pdo->prepare('SELECT id FROM room_players WHERE room_id = ? AND user_id = ?');
$member->execute([$id, $user['id']]);
if (!$member->fetch()) {
    http_response_code(403);
    exit('Join this room first.');
}
record_learning_activity((int)$user['id'], null, 'room_open', 'Opened team room ' . $room['room_code']);
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['start'])) {
    verify_csrf();
    if ((int)$room['created_by'] === (int)$user['id']) {
        $pdo->prepare('UPDATE game_rooms SET status = "active" WHERE id = ?')->execute([$id]);
    }
    redirect('room.php?id=' . $id);
}
$players = $pdo->prepare('SELECT u.username,u.xp,rp.joined_at FROM room_players rp JOIN users u ON u.id=rp.user_id WHERE rp.room_id=? ORDER BY rp.joined_at');
$players->execute([$id]);
$playerList = $players->fetchAll();
$score = $pdo->prepare('SELECT COALESCE(SUM(a.score),0) AS score,COALESCE(SUM(a.total_questions),0) AS total FROM room_players rp LEFT JOIN mission_attempts a ON a.user_id=rp.user_id WHERE rp.room_id=?');
$score->execute([$id]);
$team = $score->fetch();
$missions = $pdo->query('SELECT id,title,chapter FROM missions ORDER BY chapter,id')->fetchAll();
page_header('Team Room');
?>
<style>
.room-video-panel{margin:18px 0;padding:20px;background:#0d192b;border:1px solid #264365;border-radius:14px}
.room-video-head{display:flex;justify-content:space-between;align-items:flex-start;gap:14px;flex-wrap:wrap}
.room-video-head h2{margin:5px 0}.room-video-head p{margin:4px 0}
.room-video-actions{display:flex;gap:8px;flex-wrap:wrap}
.room-video-actions button{border:1px solid #2d6689;border-radius:7px;background:#102d48;color:#e8f7ff;padding:9px 13px;font-weight:700;cursor:pointer}
.room-video-actions button:hover:not(:disabled){border-color:#20d9f5;background:#163c5d}
.room-video-actions button:disabled{opacity:.5;cursor:default}
.room-camera-note{margin:12px 0;padding:10px 12px;border-left:3px solid #20d9f5;background:#091526;color:#c2d7ee;font-size:12px}
.room-media-status{color:#74e6ae;font:11px 'DM Mono',monospace}
.room-video-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(210px,1fr));gap:12px;margin-top:15px}
.room-video-tile{position:relative;min-height:145px;overflow:hidden;border:1px solid #2b4568;border-radius:10px;background:linear-gradient(145deg,#102440,#07101f)}
.room-video-tile video{display:block;width:100%;height:100%;min-height:145px;max-height:230px;object-fit:cover;background:#050b14}
.room-video-tile.local video{transform:scaleX(-1)}
.room-video-placeholder{display:grid;place-items:center;min-height:145px;color:#84a5c5;text-align:center}
.room-video-placeholder strong{display:block;color:#eaf4ff;font-size:14px}
.room-video-placeholder span{display:block;margin-top:6px;font-size:11px}
.room-video-label{position:absolute;left:8px;bottom:8px;padding:5px 8px;border-radius:5px;background:#030914df;color:#fff;font-size:10px}
.room-video-count{color:#9cb1ca;font:10px 'DM Mono',monospace}
.room-video-error{color:#ffb6b6;font-size:12px}
.room-activity-note{font-size:11px;color:#9aabc3}
@media(max-width:600px){.room-video-panel{padding:14px}.room-video-grid{grid-template-columns:repeat(auto-fill,minmax(145px,1fr))}}
</style>
<div class="eyebrow">TEAM ROOM / <?=e($room['room_code'])?></div>
<h1>Mission briefing room</h1>
<div class="room-code">ROOM CODE <strong><?=e($room['room_code'])?></strong></div>
<p>Status: <strong><?=e($room['status'])?></strong></p>
<section class="room-video-panel">
  <div class="room-video-head">
    <div><div class="eyebrow">LIVE SQUAD CALL / CAMERA IS NOT RECORDED</div><h2>Team video</h2><p class="muted">Your browser will ask for camera and microphone permission. If allowed, live video/audio is shared peer-to-peer with this room only. No recording or gaze analysis. Google STUN is used only to discover a network route; it does not receive your media. Direct peer calls can expose network address information to room participants.</p></div>
    <div class="room-video-actions">
      <button id="enableMedia" type="button">Enable / retry camera</button>
      <button id="muteAudio" type="button" disabled>Mute microphone</button>
      <button id="stopMedia" type="button" disabled>Turn camera off</button>
    </div>
  </div>
  <div class="room-camera-note" id="mediaStatus" role="status">Connecting to the room. Camera and microphone access will be requested by your browser.</div>
  <div class="room-video-count"><span id="onlineCount">0</span> squad members online · Allow camera access to share video/audio.</div>
  <div class="room-video-grid" id="videoGrid"></div>
  <p class="room-video-error" id="mediaError" aria-live="polite"></p>
</section>
<div class="two-col">
  <section class="panel"><h2>Squad members</h2><?php foreach($playerList as $p):?><div class="list-row"><span><?=e($p['username'])?></span><small><?= (int)$p['xp']?> XP</small></div><?php endforeach;?></section>
  <section class="panel"><h2>Team score</h2><div class="score-display"><?= (int)$team['score']?> / <?= (int)$team['total']?></div><p class="muted">Combined correct answers from room members' saved mission attempts.</p></section>
</div>
<?php if($room['status']==='waiting' && (int)$room['created_by']===(int)$user['id']):?><form method="post"><input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>"><button class="btn primary" name="start" value="1">Start team session</button></form><?php endif;?>
<section class="panel"><h2>Team mission board</h2><p>Each member can complete a safe simulation. Team progress appears on their own dashboard; refresh this room for updated team scores.</p><?php foreach($missions as $m):?><div class="list-row"><span>Chapter <?= (int)$m['chapter']?> · <?=e($m['title'])?></span><a href="play.php?id=<?=(int)$m['id']?>">Play mission →</a></div><?php endforeach;?></section>
<p class="room-activity-note">Learning activity records mission visits and completions only. The camera call does not analyze gaze, movement, or attention. Video calls require a secure context (HTTPS, or localhost) and may need a TURN service on restrictive networks.</p>
<script>
const ROOM_ID=<?= (int)$id ?>,MY_ID=<?= (int)$user['id'] ?>,MY_NAME=<?= json_encode($user['username'], JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?>,ROOM_CSRF=<?= json_encode(csrf_token()) ?>;
const makeClientToken=()=>{if(window.crypto&&crypto.randomUUID)return crypto.randomUUID().replace(/-/g,'');return Array.from(crypto.getRandomValues(new Uint8Array(16)),b=>b.toString(16).padStart(2,'0')).join('');};
let clientToken=makeClientToken();
const peerConnections=new Map(),remoteStreams=new Map(),pendingCandidates=new Map(),peerTokens=new Map();
let localStream=null,cursor=0,polling=false,left=false;
const $=id=>document.getElementById(id);
function roomForm(action,extra={}){const data=new FormData();data.append('csrf_token',ROOM_CSRF);data.append('room_id',ROOM_ID);data.append('client_token',clientToken);data.append('camera_enabled',localStream?'1':'0');data.append('action',action);Object.entries(extra).forEach(([key,value])=>data.append(key,value));return data;}
async function postRoom(action,extra={}){const response=await fetch('room_signal.php',{method:'POST',body:roomForm(action,extra)});let result;try{result=await response.json();}catch(error){throw new Error(response.status===403?'Room authorization expired. Refresh the page and sign in again.':'The room signaling service returned an invalid response.');}if(!response.ok)throw new Error(result.error||'Room connection failed.');return result;}
function makeTile(id,name,local=false){
  const key=String(id);let tile=document.querySelector('[data-video-user="'+key+'"]');
  if(tile)return tile.querySelector('video');
  tile=document.createElement('div');tile.className='room-video-tile'+(local?' local':'');tile.dataset.videoUser=key;
  const video=document.createElement('video');video.autoplay=true;video.playsInline=true;video.muted=local;
  const placeholder=document.createElement('div');placeholder.className='room-video-placeholder';placeholder.innerHTML='<div><strong></strong><span></span></div>';
  placeholder.querySelector('strong').textContent=name;
  placeholder.querySelector('span').textContent=local?'Your camera preview':'Camera is off';
  tile.append(video,placeholder);
  const label=document.createElement('span');label.className='room-video-label';label.textContent=local?name+' (you)':name;tile.append(label);
  $('videoGrid').append(tile);return video;
}
function setTileStream(id,name,stream,local=false){
  const video=makeTile(id,name,local),placeholder=video.parentElement.querySelector('.room-video-placeholder');
  if(stream){video.srcObject=stream;video.style.display='block';placeholder.style.display='none';video.play().catch(()=>{});}
  else{video.srcObject=null;video.style.display='none';placeholder.style.display='grid';}
}
async function sendSignal(peerId,type,payload){
  try{await postRoom('signal',{recipient_id:peerId,signal_type:type,payload:JSON.stringify(payload)});}
  catch(error){$('mediaError').textContent=error.message;}
}
function createPeer(peerId,name){
  const existing=peerConnections.get(peerId);if(existing)return existing;
  const pc=new RTCPeerConnection({iceServers:[{urls:'stun:stun.l.google.com:19302'}]});
  peerConnections.set(peerId,pc);
  if(localStream)localStream.getTracks().forEach(track=>pc.addTrack(track,localStream));
  else{pc.addTransceiver('video',{direction:'recvonly'});pc.addTransceiver('audio',{direction:'recvonly'});}
  pc.onicecandidate=event=>{if(event.candidate)sendSignal(peerId,'candidate',event.candidate.toJSON());};
  pc.ontrack=event=>{
    let stream=event.streams[0];
    if(!stream){stream=remoteStreams.get(peerId)||new MediaStream();if(!stream.getTracks().some(track=>track.id===event.track.id))stream.addTrack(event.track);remoteStreams.set(peerId,stream);}
    setTileStream(peerId,name,stream);
    event.track.onmute=()=>setTileStream(peerId,name,null);
    event.track.onunmute=()=>setTileStream(peerId,name,remoteStreams.get(peerId)||event.streams[0]);
  };
  pc.onconnectionstatechange=()=>{
    if(pc.connectionState==='failed')$('mediaError').textContent='Video connection failed. Your network may require a TURN relay.';
    if(pc.connectionState==='connected')$('mediaError').textContent='';
  };
  makeTile(peerId,name);
  return pc;
}
async function negotiate(peerId,name){
  const pc=createPeer(peerId,name);
  if(pc.signalingState!=='stable')return;
  const offer=await pc.createOffer();await pc.setLocalDescription(offer);await sendSignal(peerId,'offer',pc.localDescription.toJSON());
}
async function handleSignal(signal){
  const peerId=Number(signal.sender_id),name=signal.username;
  let pc=peerConnections.get(peerId);
  if(signal.signal_type==='offer'){
    pc=createPeer(peerId,name);await pc.setRemoteDescription(JSON.parse(signal.payload));
    const queued=pendingCandidates.get(peerId)||[];
    for(const candidate of queued)await pc.addIceCandidate(candidate);
    pendingCandidates.delete(peerId);
    const answer=await pc.createAnswer();await pc.setLocalDescription(answer);await sendSignal(peerId,'answer',pc.localDescription.toJSON());
  }else if(signal.signal_type==='answer'&&pc){
    await pc.setRemoteDescription(JSON.parse(signal.payload));
  }else if(signal.signal_type==='candidate'){
    const candidate=JSON.parse(signal.payload);
    if(pc&&pc.remoteDescription)await pc.addIceCandidate(candidate);
    else pendingCandidates.set(peerId,[...(pendingCandidates.get(peerId)||[]),candidate]);
  }
}
async function syncRoom(){
  if(polling||left)return;polling=true;
  try{
    const state=await postRoom('sync',{cursor});
    const online=new Set(state.players.map(person=>Number(person.user_id)));
    $('onlineCount').textContent=String(state.players.length);
    state.players.forEach(person=>{
      const peerId=Number(person.user_id),name=person.username;
      if(peerId===MY_ID)return;
      const currentToken=peerTokens.get(peerId);
      if(currentToken&&currentToken!==person.client_token){
        peerConnections.get(peerId)?.close();peerConnections.delete(peerId);remoteStreams.delete(peerId);pendingCandidates.delete(peerId);
      }
      peerTokens.set(peerId,person.client_token);
      makeTile(peerId,name);
      if(!Number(person.camera_enabled))setTileStream(peerId,name,null);
      if(MY_ID<peerId&&!peerConnections.has(peerId))negotiate(peerId,name).catch(error=>{$('mediaError').textContent='Could not start video call: '+error.message;});
    });
    for(const [peerId,pc] of peerConnections){
      if(!online.has(peerId)){pc.close();peerConnections.delete(peerId);remoteStreams.delete(peerId);document.querySelector('[data-video-user="'+peerId+'"]')?.remove();}
    }
    for(const signal of state.signals){
      try{await handleSignal(signal);}catch(error){$('mediaError').textContent='A team video update could not be processed. Refresh the room if calls do not connect.';console.warn('Could not process room call signal.',error);}
    }
    cursor=state.cursor;
  }catch(error){$('mediaStatus').textContent=error.message;}
  finally{polling=false;setTimeout(syncRoom,1500);}
}
async function enableMedia(){
  if(localStream)return;
  $('enableMedia').disabled=true;
  $('mediaError').textContent='';
  if(!navigator.mediaDevices||!navigator.mediaDevices.getUserMedia){
    $('mediaError').textContent='Camera access requires HTTPS or localhost and a browser that supports video calls.';
    $('mediaStatus').textContent='Camera unavailable. You can still participate without video.';
    $('enableMedia').disabled=false;
    return;
  }
  try{
    const stream=await navigator.mediaDevices.getUserMedia({video:true,audio:true});
    localStream=stream;setTileStream(MY_ID,MY_NAME,stream,true);
    $('mediaStatus').textContent='Camera and microphone are on. Live media is shared with room members and is not recorded.';
    $('stopMedia').disabled=false;$('muteAudio').disabled=false;$('enableMedia').textContent='Camera on';
    for(const [peerId,pc] of peerConnections){
      let renegotiate=false;
      for(const track of stream.getTracks()){
        const transceiver=pc.getTransceivers().find(item=>item.receiver.track.kind===track.kind);
        if(transceiver){
          await transceiver.sender.replaceTrack(track);
          if(transceiver.direction==='recvonly'||transceiver.direction==='inactive'){transceiver.direction='sendrecv';renegotiate=true;}
        }else{pc.addTrack(track,stream);renegotiate=true;}
      }
      if(renegotiate&&pc.signalingState==='stable')negotiate(peerId,'Room member').catch(error=>console.warn('Could not update video connection.',error));
    }
  }catch(error){
    $('mediaStatus').textContent='Camera off. You can join the room without video.';
    $('mediaError').textContent=error.name==='NotAllowedError'?'Camera/microphone permission was denied. Allow access in your browser settings, then retry.':'Could not access camera/microphone: '+error.message;
    $('enableMedia').textContent='Retry camera access';
    $('enableMedia').disabled=false;
    setTileStream(MY_ID,MY_NAME,null,true);
  }
}
$('enableMedia').addEventListener('click',enableMedia);
$('stopMedia').addEventListener('click',()=>{
  peerConnections.forEach(pc=>pc.close());peerConnections.clear();remoteStreams.clear();pendingCandidates.clear();peerTokens.clear();
  document.querySelectorAll('.room-video-tile').forEach(tile=>{if(tile.dataset.videoUser!==String(MY_ID))tile.remove();});
  if(localStream)localStream.getTracks().forEach(track=>track.stop());
  localStream=null;clientToken=makeClientToken();setTileStream(MY_ID,MY_NAME,null,true);
  $('mediaStatus').textContent='Camera and microphone are off. Room presence remains active.';
  $('stopMedia').disabled=true;$('muteAudio').disabled=true;$('enableMedia').disabled=false;$('enableMedia').textContent='Enable camera';
  syncRoom();
});
$('muteAudio').addEventListener('click',()=>{
  if(!localStream)return;
  const track=localStream.getAudioTracks()[0];if(!track)return;
  track.enabled=!track.enabled;$('muteAudio').textContent=track.enabled?'Mute microphone':'Unmute microphone';
});
function leaveRoom(){
  if(left)return;left=true;
  peerConnections.forEach(pc=>pc.close());peerConnections.clear();
  if(localStream)localStream.getTracks().forEach(track=>track.stop());
  const data=roomForm('leave');
  navigator.sendBeacon('room_signal.php',data);
}
window.addEventListener('pagehide',leaveRoom);
window.addEventListener('beforeunload',leaveRoom);
syncRoom();
enableMedia();
</script>
<?php page_footer(); ?>
