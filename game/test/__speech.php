<img id="start" src="__speech.png" width="48" height="48" alt="speech">

    <script>
        let recognition = new webkitSpeechRecognition();
        //recognition.lang = "en";
        
        let start = document.getElementById("start");

        // Voice input event
        recognition.onresult = function(event) {
            content = event.results[0][0].transcript;
            console.log(content);
        }

        // End of voice input
        recognition.onend = function() {
            start.src = "__speech.png";
            new Audio('./__stop.mp3').play();
        }

        // Click start button
        start.addEventListener("click", function() {
            recognition.start();
            start.src = "__speeching.png";
            new Audio('./__speeching.mp3').play();
        });
    </script>