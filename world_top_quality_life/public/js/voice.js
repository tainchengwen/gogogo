
function voice_success(){
    // 创建音频上下文
    var audioCtx = new AudioContext();
    // 创建音调控制对象
    var oscillator = audioCtx.createOscillator();
    // 创建音量控制对象
    var gainNode = audioCtx.createGain();
    // 音调音量关联
    oscillator.connect(gainNode);
    // 音量和设备关联
    gainNode.connect(audioCtx.destination);
    // 音调类型指定为正弦波
    oscillator.type = 'sine';
    // 设置音调频率
    oscillator.frequency.value =349.23;
    // 先把当前音量设为0
    gainNode.gain.setValueAtTime(0, audioCtx.currentTime);
    // 0.01秒时间内音量从刚刚的0变成1，线性变化
    gainNode.gain.linearRampToValueAtTime(1, audioCtx.currentTime + 0.01);
    // 声音走起
    oscillator.start(audioCtx.currentTime);
    // 1秒时间内音量从刚刚的1变成0.001，指数变化
    gainNode.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + 1);
    // 1秒后停止声音
    oscillator.stop(audioCtx.currentTime + 3);
}

function voice_error(){
    // 创建音频上下文
    var audioCtx = new AudioContext();
    // 创建音调控制对象
    var oscillator = audioCtx.createOscillator();
    // 创建音量控制对象
    var gainNode = audioCtx.createGain();
    // 音调音量关联
    oscillator.connect(gainNode);
    // 音量和设备关联
    gainNode.connect(audioCtx.destination);
    // 音调类型指定为正弦波
    oscillator.type = 'sine';
    // 设置音调频率
    oscillator.frequency.value =987.77;
    // 先把当前音量设为0
    gainNode.gain.setValueAtTime(0, audioCtx.currentTime);
    // 0.01秒时间内音量从刚刚的0变成1，线性变化
    gainNode.gain.linearRampToValueAtTime(1, audioCtx.currentTime + 0.01);
    // 声音走起
    oscillator.start(audioCtx.currentTime);
    // 1秒时间内音量从刚刚的1变成0.001，指数变化
    gainNode.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + 1);
    // 1秒后停止声音
    oscillator.stop(audioCtx.currentTime + 3);
}