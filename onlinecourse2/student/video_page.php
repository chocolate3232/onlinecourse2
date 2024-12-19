<?php
session_start();
require_once('../command/conn.php');

// ตรวจสอบว่าผู้ใช้เข้าสู่ระบบหรือยัง
if (!isset($_SESSION['member_id'])) {
    echo "<script>alert('กรุณาเข้าสู่ระบบ');</script>";
    echo "<script>window.location.href = '../index.php';</script>";
}

// ตรวจสอบว่ามีการส่งค่าของ subject_id และ lesson_id มาหรือไม่
if (isset($_GET['subject_id']) && isset($_GET['lesson_id'])) {
    $subject_id = $_GET['subject_id'];
    $lesson_id = $_GET['lesson_id'];
} else {
    // ถ้าไม่ได้รับค่าให้แสดงข้อความหรือทำการรีไดเร็กต์
    echo "ข้อมูลไม่ครบ";
    exit;
}

// ดึงข้อมูลของวิชาจากฐานข้อมูล
$query = $conn->prepare('
    SELECT s.subject_name, s.subject_code, s.subject_year, t.member_title, t.member_firstname, t.member_lastname
    FROM tb_subject2 AS s
    LEFT JOIN on_member AS t ON s.member_id = t.member_id
    WHERE s.subject_id = :subject_id
');
$query->bindParam(':subject_id', $subject_id, PDO::PARAM_INT);
$query->execute();
$subject = $query->fetch(PDO::FETCH_ASSOC);

// ดึงข้อมูลบทเรียนจากฐานข้อมูล
$lesson_query = $conn->prepare('
    SELECT * FROM tb_lesson
    WHERE subject_id = :subject_id AND lesson_id = :lesson_id
');
$lesson_query->bindParam(':subject_id', $subject_id, PDO::PARAM_INT);
$lesson_query->bindParam(':lesson_id', $lesson_id, PDO::PARAM_INT);
$lesson_query->execute();
$current_lesson = $lesson_query->fetch(PDO::FETCH_ASSOC);





// ดึงข้อมูลความคืบหน้าของการดูวิดีโอ
$progress_query = $conn->prepare('
    SELECT progress FROM tb_video_progress 
    WHERE member_id = :member_id AND subject_id = :subject_id AND lesson_id = :lesson_id
');
$progress_query->execute([
    ':member_id' => $_SESSION['member_id'],
    ':subject_id' => $subject_id,
    ':lesson_id' => $lesson_id
]);
$saved_progress = $progress_query->fetchColumn() ?: 0;

// ตรวจสอบ URL ของ YouTube
function isYouTubeUrl($url) {
    return preg_match('/^(https?:\/\/)?(www\.)?(youtube\.com|youtu\.?be)\/embed\/.+$/', $url);
}

function getYouTubeVideoId($url) {
    if (preg_match('/(?:embed\/|v\/|.+\?v=|youtu\.be\/)([^&\n]{11})/', $url, $matches)) {
        return $matches[1];
    }
    return null;
}
$studentQuery = $conn->prepare('SELECT member_title, member_firstname, member_lastname FROM on_member WHERE member_id = :member_id');
$studentQuery->bindParam(':member_id', $_SESSION['member_id']);
$studentQuery->execute();
$student = $studentQuery->fetch(PDO::FETCH_ASSOC);



?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>บทเรียน - <?php echo htmlspecialchars($subject['subject_name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="video.css">
</head>
<style>
    
       #loadingSpinner {
    display: none;
    justify-content: center;
    align-items: center;
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    z-index: 9999;
    text-align: center;
    background-color: rgba(0, 0, 0, 0.7);
    color: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    width: 350px;
    animation: fadeIn 0.5s ease-out;
}

#loadingMessage {
    font-size: 18px;
    font-weight: bold;
    margin: 0;
    padding-top: 10px;
    text-align: center;
}

.spinner-container {
    display: flex;
    flex-direction: column;
    align-items: center;
}

#loadingSpinner .spinner-border {
    width: 50px;
    height: 50px;
    margin-bottom: 10px;
    border-color: #d81b60; /* เปลี่ยนสี spinner */
    border-top-color: transparent;
}

#loadingSpinner p {
    font-size: 18px;
    font-weight: bold;
    margin: 0;
    padding-top: 10px;
}

@keyframes fadeIn {
    0% {
        opacity: 0;
    }
    100% {
        opacity: 1;
    }
}
</style>
<body>
<div id="loadingSpinner">
    <div class="spinner-container">
        <div class="spinner-border" role="status"></div>
        <p id="loadingMessage">กำลังโหลด...</p>
    </div>
</div>
       <!-- ข้อความเตือนเมื่อเปิดในโหมดแนวตั้ง -->
       <div class="landscape-warning">
        <p>กรุณาหมุนโทรศัพท์ของคุณให้เป็นแนวนอนเพื่อดูเนื้อหา</p>
    </div>
<div class="navbar">
    <div class="left">
        <span class="navbar-title"><b><font color = 'white'>LMS </span></font></b>
    </div>
    
    <div class="right">
          <!-- ปุ่มกลับหน้าหลัก -->
          <button class="btn"  id="goHomeBtn" onclick="window.location.href='user_dashboard.php'" >
            <i class="fas fa-home"></i> หน้าหลัก
        </button>
        <span class="user-info"><i class="fas fa-user"></i> <?php echo htmlspecialchars($student['member_title'] . ' ' . $student['member_firstname'] . ' ' . $student['member_lastname']); ?></span>
        </button>
       
    </div>
</div>

<!-- เนื้อหาของบทเรียน -->
<div class="container">
    <h1><?php echo htmlspecialchars($subject['subject_name']); ?></h1>
    <div class="subject-info">
        <p>รหัสวิชา: <?php echo htmlspecialchars($subject['subject_code']); ?></p>
        <p>ปีการศึกษา: <?php echo htmlspecialchars($subject['subject_year']); ?></p>
        <p>ครูผู้สอน: <?php echo htmlspecialchars($subject['member_title'] . $subject['member_firstname'] . ' ' . $subject['member_lastname']); ?></p>
    </div>

 <!-- ปุ่มและแถบความคืบหน้า -->
<button id="preTestBtn" class="disabled">📝 แบบทดสอบก่อนเรียน</button>
<div id="progressText">ความคืบหน้าในการดู: <span id="loadingText">กำลังโหลด...</span></div>
<div class="progress-bar">
    <div class="progress-bar-fill" id="progressFill" style="width: 0%;"></div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        // ค่าความคืบหน้าที่ได้จาก PHP
        const savedProgress = <?php echo json_encode($saved_progress); ?>; 
        
        // แสดงค่าความคืบหน้าเมื่อโหลดเสร็จ
        const loadingText = document.getElementById("loadingText");
        const progressFill = document.getElementById("progressFill");
        
        if (savedProgress !== null) {

            // อัปเดตแถบความคืบหน้า
            progressFill.style.width = savedProgress + "%";
        }
    });
</script>


    <div id="unlockMessage">สามารถทำแบบฝึกหัดและแบบทดสอบหลังเรียนได้เมื่อดูวีดิโอถึง 90%</div>
    
    <div class="video-wrapper">
    <?php if (!empty($current_lesson['video_url'])): ?>
        <?php if (isYouTubeUrl($current_lesson['video_url'])): ?>
            <iframe 
                id="videoPlayer" 
                src="https://www.youtube.com/embed/<?php echo getYouTubeVideoId($current_lesson['video_url']); ?>?controls=0&autoplay=1&mute=1&enablejsapi=1&rel=0&modestbranding=1" 
                frameborder="0" 
                allow="autoplay" 
                allowfullscreen>
            </iframe>
        <?php else: ?>
            <video id="videoPlayerHTML5" autoplay playsinline>
                <source src="<?php echo htmlspecialchars($current_lesson['video_url']); ?>" type="video/mp4">
            </video>
        <?php endif; ?>
    <?php else: ?>
        <p class="no-video-alert">ไม่มีวีดิโอสำหรับบทเรียนนี้</p>
    <?php endif; ?>
</div>

        <div class="controls">
            <button id="playPauseBtn">⏯️ หยุด/เล่น</button>
            <button id="fullscreenBtn">🖥️ เต็มหน้าจอ</button>
            <div class="volume-control">
                <span class="volume-label">ปรับระดับเสียง:</span>
                <button id="muteBtn">🔊 เปิดเสียง</button>
                <button id="volumeDown">-</button>
                <div class="volume-bar">
                    <div class="volume-fill" id="volumeFill" style="width: 100%;"></div>
                </div>
                <button id="volumeUp">+</button>
            </div>
            <button id="exerciseBtn" class="disabled" disabled>📝 แบบฝึกหัด</button>
            <button id="quizBtn" class="disabled" disabled>📝 แบบทดสอบหลังเรียน</button>
        </div>
    </div>
</div>
<script>
    function showLoadingSpinner() {
        document.getElementById('loadingSpinner').style.display = 'flex'; // แสดงสปินเนอร์
    }

    // ฟังก์ชั่นในการซ่อนสปินเนอร์
 function hideLoadingSpinner() {
        document.getElementById('loadingSpinner').style.display = 'none'; // ซ่อนสปินเนอร์
    }

    document.getElementById("goHomeBtn").addEventListener("click", function() {
    showLoadingSpinner();
    // ทำการนำทางไปที่หน้าหลัก (ตัวอย่างใช้ setTimeout เพื่อจำลองเวลาโหลด)
    setTimeout(function() {
        window.location.href = "user_dashboard.php";  // เปลี่ยนเป็น URL ของหน้าหลักของคุณ
    }, 2000); // ตัวอย่างรอ 2 วินาที
});

document.getElementById("exerciseBtn").addEventListener("click", function() {
    showLoadingSpinner();
    // ทำการนำทางไปที่แบบฝึกหัด (ตัวอย่างใช้ setTimeout เพื่อจำลองเวลาโหลด)
    setTimeout(function() {
        window.location.href = "student_files.php";  // เปลี่ยนเป็น URL ของหน้าฝึกหัดของคุณ
    }, 2000); // ตัวอย่างรอ 2 วินาที
});

</script>

</body>
</html>

<script>
  // ห้ามการคลิกที่วิดีโอ
  document.getElementById("video").style.pointerEvents = "none";

  // ห้ามการใช้งานแป้นพิมพ์
  window.addEventListener("keydown", function(event) {
    event.preventDefault();
  });
</script>
    <script>
              window.onload = function() {
            var iframe = document.getElementById('videoPlayer');
            var videoProgress = <?php echo $saved_progress; ?>; // ดึงค่าความคืบหน้าจาก PHP
            
            // ตรวจสอบว่าเกิน 99% หรือไม่
            if (videoProgress >= 99) {
                // หากดูเกิน 99% แล้ว ปิดการเล่นอัตโนมัติ
                var src = iframe.src;
                iframe.src = src.replace("autoplay=1", "autoplay=0");
            }
        };

        // ฟังก์ชันที่ใช้ดึงค่า progress จากฐานข้อมูล
        function getVideoProgress() {
            return 50; // เปลี่ยนเป็นค่าจริงจากฐานข้อมูลหรือเซสชัน
        }

        var savedProgress = <?php echo json_encode($saved_progress); ?>;
        var isYouTube = <?php echo json_encode(isYouTubeUrl($current_lesson['video_url'])); ?>;
        var playPauseBtn = document.getElementById('playPauseBtn');
        var volumeFill = document.getElementById('volumeFill');
        var volumeDown = document.getElementById('volumeDown');
        var volumeUp = document.getElementById('volumeUp');
        var progressFill = document.getElementById('progressFill');
        var progressText = document.getElementById('progressText');
        const preTestBtn = document.getElementById('preTestBtn');
        const exerciseBtn = document.getElementById('exerciseBtn');
        const quizBtn = document.getElementById('quizBtn');     
        const unlockPercentage = 90; // เปอร์เซ็นต์ที่ต้องดูเพื่อปลดล็อกปุ่ม
   

const unlockMessage = document.getElementById('unlockMessage'); // อ้างอิงถึงข้อความเตือน

function checkPreTestStatus() {
            // สมมติว่าค่าจากฐานข้อมูลบ่งบอกว่าผู้เรียนทำแบบทดสอบเสร็จหรือยัง
            const preTestCompleted = false; // เปลี่ยนค่าตามสถานะจริงจากฐานข้อมูล

            if (preTestCompleted) {
                preTestBtn.disabled = true;
                preTestBtn.classList.add('disabled');
                exerciseBtn.disabled = false;
                exerciseBtn.classList.remove('disabled');
            } else {
                preTestBtn.disabled = false;
                preTestBtn.classList.remove('disabled');
            }
        }

      // คลิกปุ่มเริ่มแบบทดสอบก่อนเรียน
preTestBtn.addEventListener('click', function() {
    // เปิดหน้าต่างใหม่
    window.open('http://www.ctnphrae.com/th/student-list-exam.html', '_blank', 'width=800,height=600');
});


        function unlockButtons(currentTime, duration) {
    if (duration > 0) {
        const progressPercent = Math.floor((currentTime / duration) * 100);
        if (progressPercent >= unlockPercentage) {
            exerciseBtn.disabled = false; // ปลดล็อกปุ่มแบบฝึกหัด
            quizBtn.disabled = false; // ปลดล็อกปุ่มแบบทดสอบหลังเรียน
            exerciseBtn.classList.remove('disabled');
            quizBtn.classList.remove('disabled');
            unlockMessage.style.display = 'none'; // ซ่อนข้อความเตือน
        } else {
            exerciseBtn.disabled = true; // ปิดล็อกปุ่มแบบฝึกหัด
            quizBtn.disabled = true; // ปิดล็อกปุ่มแบบทดสอบหลังเรียน
            exerciseBtn.classList.add('disabled');
            quizBtn.classList.add('disabled');
            unlockMessage.style.display = 'block'; // แสดงข้อความเตือน
        }
    }
}



function updateProgress(currentTime, duration) {
    if (duration > 0) {
        let progressPercent = Math.floor((currentTime / duration) * 100);
        progressFill.style.width = progressPercent + "%";
        progressText.textContent = "ความคืบหน้าในการดู: " + progressPercent + "%";

        saveProgress(currentTime, progressPercent); // ตรวจสอบการเรียกใช้ saveProgress
        unlockButtons(currentTime, duration);
    }
}

function saveProgress(progress, progressPercent) {
    fetch('save_progress.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'progress=' + progress + '&progress_percen=' + progressPercent + '&subject_id=' + <?php echo json_encode($subject_id); ?> + '&lesson_id=' + <?php echo json_encode($current_lesson['lesson_id']); ?>
    })
    .then(response => response.text())
    .then(data => console.log("Response from save_progress.php:", data)) // Debug log
    .catch(error => console.error('Error:', error));
}

        function updateVolumeBar(volume) {
            volumeFill.style.width = volume + "%";
        }
        let isMuted = true;  // ตั้งค่าพื้นฐานให้เสียงปิด

// ฟังก์ชันเปิดเสียงหรือปิดเสียง
function toggleMute() {
    if (isMuted) {
        // เปิดเสียง
        if (isYouTube) {
            player.unMute();  // สำหรับ YouTube
            player.setVolume(100); // ตั้งระดับเสียงเป็น 100
        } else {
            videoPlayer.muted = false;  // สำหรับ HTML5 video
            videoPlayer.volume = 1;  // ตั้งระดับเสียงเป็น 100%
        }
        document.getElementById("muteBtn").textContent = "🔇 ปิดเสียง";  // เปลี่ยนข้อความปุ่ม
    } else {
        // ปิดเสียง
        if (isYouTube) {
            player.mute();  // สำหรับ YouTube
        } else {
            videoPlayer.muted = true;  // สำหรับ HTML5 video
        }
        document.getElementById("muteBtn").textContent = "🔊 เปิดเสียง";  // เปลี่ยนข้อความปุ่ม
    }
    isMuted = !isMuted;  // สลับสถานะเสียง
}

// เพิ่ม event listener ให้กับปุ่มเปิดเสียง
document.getElementById("muteBtn").addEventListener("click", toggleMute);
        if (isYouTube) {
    var player;
    function onYouTubeIframeAPIReady() {
        player = new YT.Player('videoPlayer', {
            events: {
                'onReady': onPlayerReady,
                'onStateChange': onPlayerStateChange
            }
        });
    }

    function onPlayerReady(event) {
        player.seekTo(savedProgress, true); // ตั้งค่าให้เริ่มที่ตำแหน่งที่บันทึก
        // เริ่มต้นการอัปเดตความก้าวหน้าเมื่อเล่น
        setInterval(() => {
            let currentTime = player.getCurrentTime();
            let duration = player.getDuration();
            updateProgress(currentTime, duration);
        }, 1000);
    }

    function onPlayerStateChange(event) {
        if (event.data == YT.PlayerState.PAUSED) {
            playPauseBtn.textContent = '⏯️ เล่น';
        } else if (event.data == YT.PlayerState.PLAYING) {
            playPauseBtn.textContent = '⏸️ หยุด';
        }
        
    }

    playPauseBtn.addEventListener('click', function() {
        if (player.getPlayerState() === YT.PlayerState.PAUSED) {
            player.playVideo();
            playPauseBtn.textContent = '⏸️ หยุด';
        } else {
            player.pauseVideo();
            playPauseBtn.textContent = '⏯️ เล่น';
        }
    });
} else {
    var videoPlayer = document.getElementById('videoPlayerHTML5');
    videoPlayer.currentTime = savedProgress; // ตั้งเวลาเริ่มต้น
    videoPlayer.addEventListener('timeupdate', function() {
        updateProgress(videoPlayer.currentTime, videoPlayer.duration);
    });
    playPauseBtn.addEventListener('click', function() {
        if (videoPlayer.paused) {
            videoPlayer.play();
            playPauseBtn.textContent = '⏸️ หยุด';
        } else {
            videoPlayer.pause();
            playPauseBtn.textContent = '⏯️ เล่น';
        }
    });
}


        window.onbeforeunload = function() {
            if (isYouTube) {
                localStorage.setItem('youtubeVideoProgress', player.getCurrentTime());
            } else {
                localStorage.setItem('html5VideoProgress', videoPlayer.currentTime);
            }
        };

        volumeDown.addEventListener('click', function() {
            if (isYouTube) {
                let volume = player.getVolume();
                if (volume > 0) {
                    volume -= 10;
                    player.setVolume(volume);
                    updateVolumeBar(volume);
                }
            } else {
                let volume = videoPlayer.volume * 100;
                if (volume > 0) {
                    volume -= 10;
                    videoPlayer.volume = volume / 100;
                    updateVolumeBar(volume);
                }
            }
        });

        volumeUp.addEventListener('click', function() {
            if (isYouTube) {
                let volume = player.getVolume();
                if (volume < 100) {
                    volume += 10;
                    player.setVolume(volume);
                    updateVolumeBar(volume);
                }
            } else {
                let volume = videoPlayer.volume * 100;
                if (volume < 100) {
                    volume += 10;
                    videoPlayer.volume = volume / 100;
                    updateVolumeBar(volume);
                }
            }
        });
        document.getElementById('exerciseBtn').addEventListener('click', function() {
    window.location.href = 'student_files.php?lesson_id=' + <?php echo json_encode($current_lesson['lesson_id']); ?>;
});


document.getElementById('quizBtn').addEventListener('click', function() {
    // ตรวจสอบว่า lesson_id มีค่า
    var lessonId = <?php echo json_encode($current_lesson['lesson_id']); ?>;
    if (lessonId) {
         // เปิดหน้าต่างใหม่
    window.open('http://www.ctnphrae.com/th/student-list-exam.html', '_blank', 'width=800,height=600'); 
    } else {
        // ถ้าไม่มีค่า lesson_id แสดงข้อความ
        alert('ไม่พบรหัสบทเรียน');
    }
});
window.onload = function() {
            checkPreTestStatus(); // เช็คสถานะการทำแบบทดสอบก่อนเรียน
        };
        // เมื่อผู้ใช้ทำแบบทดสอบก่อนเรียนเสร็จ
function unlockPostTestButton() {
    // AJAX เรียกใช้เพื่ออัปเดตสถานะในฐานข้อมูล
    fetch('update_pretest_status.php', {
        method: 'POST',
        body: JSON.stringify({ userId: userId }), // ส่ง user ID เพื่ออัปเดตสถานะ
        headers: { 'Content-Type': 'application/json' }
    }).then(response => response.json())
      .then(data => {
          if (data.success) {
              // ปลดล็อคปุ่มแบบทดสอบหลังเรียน
              document.getElementById('postTestButton').disabled = false;
          }
      }).catch(error => console.error('Error:', error));
      
}

const fullscreenBtn = document.getElementById('fullscreenBtn');

// ฟังก์ชันสำหรับเข้าสู่โหมดเต็มหน้าจอ
fullscreenBtn.addEventListener('click', function () {
    let videoElement;

    if (isYouTube) {
        videoElement = document.getElementById('videoPlayer');
    } else {
        videoElement = document.getElementById('videoPlayerHTML5');
    }

    if (videoElement.requestFullscreen) {
        videoElement.requestFullscreen();
    } else if (videoElement.mozRequestFullScreen) { // Firefox
        videoElement.mozRequestFullScreen();
    } else if (videoElement.webkitRequestFullscreen) { // Chrome, Safari, Opera
        videoElement.webkitRequestFullscreen();
    } else if (videoElement.msRequestFullscreen) { // IE/Edge
        videoElement.msRequestFullscreen();
    }
});

window.addEventListener("orientationchange", function() {
    if (window.orientation === 0) {
        // ถ้าเป็นโหมดแนวตั้ง
        document.querySelector(".landscape-warning").style.display = "block";  // แสดงข้อความเตือน

        // หยุดการเล่นวิดีโอ
        if (isYouTube) {
            player.pauseVideo();
        } else {
            videoPlayer.pause();
        }
    } else {
        // ถ้าเป็นโหมดแนวนอน
        document.querySelector(".landscape-warning").style.display = "none";  // ซ่อนข้อความเตือน

        // เริ่มเล่นวิดีโอใหม่
        if (isYouTube) {
            player.playVideo();
        } else {
            videoPlayer.play();
        }
    }
});


    </script>

    <script src="https://www.youtube.com/iframe_api"></script>
    <script>
        document.addEventListener('keydown', function(event) {
    // ป้องกันการกดปุ่มลูกศรซ้ายและขวาเพื่อเลื่อนคลิปวีดิโอ
    if (event.key === 'ArrowLeft' || event.key === 'ArrowRight') {
        event.preventDefault();
    }
});

</script>

</body>
</html>
