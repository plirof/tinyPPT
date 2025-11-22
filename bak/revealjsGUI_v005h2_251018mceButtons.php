<?php
/*
ppt-reveal
NOTE reveal.js does NOT run in old Iron browser.!!! (so use any tinymce you want)
v005h2 251017 : Tinymce editor buttons
v005h1 251017 : Theme test
v005g 251017 : Translation fixes
v005f 251017 : background color ,v005e : transitions , v005c  Translation + Added button to reset/delete presentation
v005a 251017 : initial version tinymce5 , reveal.js




*/
// Configuration: set to true to enable server save/load
$use_server = false;
$save_to_file = true;

// Path to JSON data file
$data_file = 'slides_data.json';

// Handle server save/load requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $json_data = $_POST['data'] ?? '';
        if ($json_data && $use_server) {
            file_put_contents($data_file, $json_data);
            echo json_encode(['status' => 'success']);
            exit;
        }
    } elseif ($action === 'load') {
        if ($use_server && file_exists($data_file)) {
            $json_data = file_get_contents($data_file);
            echo $json_data;
            exit;
        } else {
            echo json_encode([]);
            exit;
        }
    }
    // Invalid request
    echo json_encode(['status' => 'error']);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Slide Editor with Reveal.js</title>
<!-- TinyMCE CDN 
<script src="https://cdn.jsdelivr.net/npm/@tinymce/tinymce-webcomponent/dist/tinymce-webcomponent.min.js" referrerpolicy="origin"></script>


<script src="tinymce/js/tinymce/tinymce.min.js" referrerpolicy="origin"></script>

-->
<script src="tinymce/js/tinymce/tinymce.min.js" referrerpolicy="origin"></script>

<!-- Reveal.js CDN - ok WORKS->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/reveal.js/dist/reveal.css" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/reveal.js/dist/theme/white.css" />
<script src="https://cdn.jsdelivr.net/npm/reveal.js/dist/reveal.js"></script>
-->

<link rel="stylesheet" href="reveal.js-5.2.1dist/reveal.css" />
<!-- <link rel="stylesheet" href="reveal.js-5.2.1dist/theme/white.css" /> -->
<link rel="stylesheet" href="reveal.js-5.2.1dist/theme/sky.css" />
<script src="reveal.js-5.2.1dist/reveal.js"></script>




<script>

  let currentLang = 'el'; // or dynamically set based on user preference
</script>

<style>
  body {
    font-family: Arial, sans-serif;
    margin: 0;
    height: 100vh;
    display: flex;
    flex-direction: column;
  }
  h1 {
    text-align: center;
    margin: 10px 0;
  }
  #top-controls {
    display: flex;
    justify-content: center;
    flex-wrap: wrap;
    gap: 8px;
    margin: 10px;
  }
  button {
    padding: 8px 12px;
    font-size: 0.9em;
  }
  #slide-list {
    max-height: 150px;
    overflow-y: auto;
    padding: 0 10px;
    margin: 0;
  }
  #slide-list li {
    background: #f0f0f0;
    margin: 5px 0;
    padding: 6px 10px;
    cursor: grab;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-radius: 4px;
  }
  .slide-title {
    flex: 1;
  }
  .slide-actions button {
    margin-left: 5px;
    padding: 4px 8px;
    font-size: 0.8em;
  }
  #editor-container {
    display: none;
    padding: 10px;
    background: #fff;
    border-top: 1px solid #ccc;
  }
  #editor-container h2 {
    margin-top: 0;
  }
  #slide-title-input {
    width: 100%;
    font-size: 1em;
    margin-bottom: 10px;
  }
  #editor-buttons {
    text-align: right;
    margin-top: 10px;
  }
  #slide-show-area {
    flex: 1;
    display: flex;
    flex-direction: column;
  }
  #reveal {
    flex: 1;
  }
</style>
</head>
<body>

<h1>Slide Presentation Editor</h1>
<div id="top-controls">
  <button id="add-slide">Add Slide</button>
  <button id="generate-html">Generate Standalone HTML</button>
  <label for="theme-selector">Select Theme:</label>
  <select id="theme-selector">
    <option value="white.css">White</option>
    <option value="sky.css">Sky</option>
    <option value="dracula.css">Dracula</option>
    <option value="blood.css">Blood</option>
    <!-- add other themes as needed -->
  </select>
  <button id="save-local">Save to Local Storage</button>
  <button id="load-local">Load from Local Storage</button>
  <?php if ($use_server): ?>
  <button id="save-server">Save to Server</button>
  <button id="load-server">Load from Server</button>
  <?php endif; ?>
  
  <button id="new-presentation">New Presentation</button>
</div>

<div id="slide-list"></div>

<div id="editor-container">
  <h2 id="edit-title" >Editing Slide</h2>
  <input type="text" id="slide-title-input" placeholder="Slide Title" />
  <textarea id="slide-content"></textarea>
  <div id="editor-buttons">
    <button id="save-slide">Save</button>
    <button id="cancel-edit">Cancel</button>
  </div>
  <select id="slide-transition">
    <option value="slide">Slide</option>
    <option value="fade">Fade</option>
    <option value="convex">Convex</option>
    <option value="concave">Concave</option>
    <option value="zoom">Zoom</option>
    <option value="none">None</option>
  </select>
  <label for="slide-bgcolor" id="slide-bgcolor-label">Background Color:</label>
  <select id="slide-bgcolor">
    <option value="White">White</option>
  </select>
</div>

<div id="slide-show-area">
  <div class="reveal" id="reveal">
    <div class="slides" id="slides-container"></div>
  </div>
</div>

<script>
let slides = [];
let currentEditIndex = null;
let currentSlideIndex = 0;
const useServer = <?php echo $use_server ? 'true' : 'false'; ?>;
const dataFile = '<?php echo $data_file; ?>';
const save_to_file = '<?php echo $save_to_file; ?>';
let selectedTheme = 'sky.css'; // default theme
const colorNames = [
  'white', 'black', 'red', 'blue', 'green', 'yellow', 'orange', 'purple', 'pink', 'gray'
];

// Initialize TinyMCE
tinymce.init({
  selector: '#slide-content',
  language: 'el', 
  height: 300,
  //menubar: false,
  plugins: 'lists link image table code',
  //toolbar: 'undo redo | formatselect | bold italic | alignleft aligncenter alignright | bullist numlist | link image table'

    //selector:'textarea',
    //language: 'el',
    entity_encoding: "raw",
    //height: "300",
    menubar: 'file edit view insert format tools table help',
    //plugins: [             "table","image",     ],    
    toolbar: 'undo redo | aidialog | bold italic underline strikethrough | fontfamily fontsize | outdent indent |  numlist bullist | backcolor removeformat | forecolor | pagebreak | charmap emoticons | fullscreen  preview save print | insertfile image media template link anchor codesample | alignleft aligncenter alignright alignjustify | styles | code', //fontsizeinput
    font_size_formats: '14pt 16pt 18pt 24pt 36pt 48pt 54pt 60pt', 
    table_toolbar : "" , //Disables the internal table popup toolbar



});



// Initialize Reveal.js
Reveal.initialize({
  controls: false,
  slideNumber: false,
  history: false,
  width: "100%",
  height: "100%",
  embedded: true
});

// Load data from local storage or server
window.onload = function() {
  if (localStorage.getItem('slidesData')) {
    slides = JSON.parse(localStorage.getItem('slidesData'));
    renderSlideList();
  } else if (useServer) {
    fetchDataFromServer();
  }
  updateSlideDisplay();
};

function fetchDataFromServer() {
  fetch('', {method: 'POST', headers: {'Content-Type':'application/x-www-form-urlencoded'}, body: 'action=load'})
    .then(res => res.json())
    .then(data => {
      if (Array.isArray(data) && data.length > 0) {
        slides = data;
        renderSlideList();
        updateSlideDisplay();
      }
    });
}

function saveDataToLocal() {
  localStorage.setItem('slidesData', JSON.stringify(slides));
  /*alert(' */ console.log('Saved to local storage');
}
function loadDataFromLocal() {
  const data = localStorage.getItem('slidesData');
  if (data) {
    slides = JSON.parse(data);
    renderSlideList();
    updateSlideDisplay();
  } else {
    /*alert(' */ console.log('No data in local storage');
  }
}
function saveDataToServer() {
  fetch('', {
    method: 'POST',
    headers: {'Content-Type':'application/x-www-form-urlencoded'},
    body: 'action=save&data=' + encodeURIComponent(JSON.stringify(slides))
  })
  .then(res => res.json())
  .then(resp => {
    if (resp.status === 'success') {
      /*alert(' */ console.log('Data saved to server');
    } else {
      /*alert(' */ console.log('Failed to save to server');
    }
  });
}
function loadDataFromServer() {
  fetch('', {method: 'POST', headers: {'Content-Type':'application/x-www-form-urlencoded'}, body: 'action=load'})
    .then(res => res.json())
    .then(data => {
      if (Array.isArray(data) && data.length > 0) {
        slides = data;
        renderSlideList();
        updateSlideDisplay();
        /*alert(' */ console.log('Loaded data from server');
      } else {
        /*alert(' */ console.log('No data on server');
      }
    });
}

function renderSlideList() {
  const list = document.getElementById('slide-list');
  list.innerHTML = '';
  slides.forEach((slide, index) => {
    const li = document.createElement('li');
    li.draggable = true;
    li.dataset.index = index;

    const titleSpan = document.createElement('span');
    titleSpan.className = 'slide-title';
    titleSpan.textContent = slide.title || 'Untitled';

    const actionsDiv = document.createElement('div');
    actionsDiv.className = 'slide-actions';

    const editBtn = document.createElement('button');
    editBtn.textContent = 'Edit';
    editBtn.onclick = () => editSlide(index);

    const deleteBtn = document.createElement('button');
    deleteBtn.textContent = 'Delete';
    deleteBtn.onclick = () => { deleteSlide(index); };

    actionsDiv.appendChild(editBtn);
    actionsDiv.appendChild(deleteBtn);

    li.appendChild(titleSpan);
    li.appendChild(actionsDiv);

    // Drag & Drop
    li.ondragstart = (e) => {
      e.dataTransfer.setData('text/plain', index);
    };
    li.ondragover = (e) => {
      e.preventDefault();
    };
    li.ondrop = (e) => {
      e.preventDefault();
      const fromIndex = e.dataTransfer.getData('text/plain');
      const toIndex = index;
      moveSlide(fromIndex, toIndex);
    };

    list.appendChild(li);
  });
}

function moveSlide(from, to) {
  from = parseInt(from);
  to = parseInt(to);
  if (from === to) return;
  const slide = slides.splice(from,1)[0];
  slides.splice(to,0,slide);
  renderSlideList();
  updateSlideDisplay();
  saveAll();
}

function addSlide() {
  const newSlide = { title: 'New Slide', content: '<p></p>',transition: 'slide' };
  slides.push(newSlide);
  renderSlideList();
  updateSlideDisplay();
  saveAll();
}

function deleteSlide(index) {
  if (confirm('Delete this slide?')) {
    slides.splice(index,1);
    renderSlideList();
    updateSlideDisplay();
    saveAll();
  }
}

function editSlide(index) {
  currentEditIndex = index;
  const slide = slides[index];
  document.getElementById('slide-title-input').value = slide.title || '';
  tinymce.get('slide-content').setContent(slide.content || '');
  document.getElementById('editor-container').style.display = 'block';
  // Set transition dropdown
  document.getElementById('slide-transition').value = slide.transition || 'slide';
  // Set background color dropdown
  document.getElementById('slide-bgcolor').value = slide.bgcolor || '';
  // Show editor
  document.getElementById('editor-container').style.display = 'block';
}

function saveSlide() {
  const index = currentEditIndex;
  if (index === null) return;
  const title = document.getElementById('slide-title-input').value;
  const content = tinymce.get('slide-content').getContent();
  const transition = document.getElementById('slide-transition').value;
  const bgcolor = document.getElementById('slide-bgcolor').value;
  slides[index] = { title, content, transition, bgcolor };
  document.getElementById('editor-container').style.display = 'none';
  renderSlideList();
  updateSlideDisplay();
  saveAll();
}

function cancelEdit() {
  document.getElementById('editor-container').style.display = 'none';
}

function saveAll() {
  saveDataToLocal();
  if (useServer) {
    saveDataToServer();
  }
}

// Functions for Reveal.js slide management
function updateSlideDisplay() {
  // Clear existing slides
  const container = document.getElementById('slides-container');
  container.innerHTML = '';
  // Optional: determine the most common transition or a default
  // For simplicity, use the first slide's transition or default
  const defaultTransition = slides.length > 0 && slides[0].transition ? slides[0].transition : 'slide';
  // Set Reveal.js global transition
  //Reveal.configure({ transition: defaultTransition });
  // Rebuild slides
  slides.forEach((slide) => {
    const section = document.createElement('section');
    section.setAttribute('data-transition', defaultTransition);
    // Apply background color if set
    if (slide.bgcolor) {
      section.setAttribute('data-background-color',slide.bgcolor);      
    } 
    //section.innerHTML = '<h2>' + slide.title + '</h2>' + slide.content;
    section.innerHTML = '<h2>' + slide.title + '</h2>' + slide.content;
    container.appendChild(section);
  });
  // Initialize or update Reveal
  Reveal.sync();
}

// Buttons event handlers
document.getElementById('add-slide').onclick = addSlide;
document.getElementById('save-slide').onclick = saveSlide;
document.getElementById('cancel-edit').onclick = cancelEdit;

document.getElementById('save-local').onclick = saveDataToLocal;
document.getElementById('load-local').onclick = loadDataFromLocal;

document.getElementById('new-presentation').onclick = function() {
  //if (confirm(messages.confirmNew)) {
    slides = [];
    renderSlideList();
    updateSlideDisplay();
    saveAll();
  //}
};

<?php if ($use_server): ?>
document.getElementById('save-server').onclick = saveDataToServer;
document.getElementById('load-server').onclick = loadDataFromServer;
<?php endif; ?>

// Generate standalone HTML with Reveal.js
/* ORIGINAL that just downloaded the html
document.getElementById('generate-html').onclick = function() {
  const htmlContent = generateRevealHTML();
  const blob = new Blob([htmlContent], {type: 'text/html'});
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = 'presentation.html';
  a.click();
  URL.revokeObjectURL(url);
};
*/
document.getElementById('generate-html').onclick = function() {
  const htmlContent = generateRevealHTML();
  const blob = new Blob([htmlContent], {type: 'text/html'});
  const url = URL.createObjectURL(blob);
  // Open in a new tab
  window.open(url, 'presentation');
  // Note: Do not revoke the URL immediately, as the new tab might still be loading

  if(save_to_file) {
    //temp save to FILE
    const a = document.createElement('a');
    a.href = url;
    a.download = 'presentation.html';
    a.click();
    URL.revokeObjectURL(url);  
  }

};

document.getElementById('theme-selector').addEventListener('change', function() {
  selectedTheme = this.value;
});

function generateRevealHTML() {
  const slidesData = JSON.stringify(slides);
  return `
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Presentation Reveal.js </title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/reveal.js/dist/reveal.css" />
<!-- <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/reveal.js/dist/theme/white.css" /> -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/reveal.js/dist/theme/${selectedTheme}" />
<style>
body { margin:0; }
.reveal { height:100vh; }
</style>
</head>
<body>
<div class="reveal" id="reveal">
<div class="slides" id="slides-container"></div>
</div>
<script src="https://cdn.jsdelivr.net/npm/reveal.js/dist/reveal.js"><`+`/script>
<script>
const slides = ${slidesData};
const revealContainer = document.querySelector('#slides-container');
revealContainer.innerHTML = '';
slides.forEach(slide => {
  const section = document.createElement('section');
  section.setAttribute('data-transition', slide.transition);
  // Apply background color if set
  if (slide.bgcolor) {
      section.setAttribute('data-background-color',slide.bgcolor);      
  } 
  section.innerHTML = '<h2>' + slide.title + '</h2>' + slide.content;
  revealContainer.appendChild(section);
});
Reveal.initialize({ controls: true, slideNumber: true });
<`+`/script>
</body>
</html>
`;
}
</script>

<script>
  // Define translations
  const translations = {
    en: {
      title: "Slide Presentation Editor",
      addSlide: "Add Slide",
      saveLocal: "Save to Local Storage",
      loadLocal: "Load from Local Storage",
      saveServer: "Save to Server",
      loadServer: "Load from Server",
      generateHTML: "Generate Standalone HTML",
      newPresentation: "New Presentation",
      edit: "Edit",
      delete: "Delete",
      confirmDelete: "Delete this slide?",
      cancel: "Cancel",
      save: "Save",
    },
    el: {
      title: "Slide Presentation Editor",
      addSlide: "+ Slide",
      saveLocal: "Αποθήκευσε Τοπικά",
      loadLocal: "Φόρτωσε τοπικά",
      saveServer: "Αποθήκευσε στον Server",
      loadServer: "Φόρτωσε από Server",
      generateHTML: "Προβολή παρουσίασης",
      newPresentation: "Νέα παρουσίαση",
      edit: "Επεξεργασία",
      delete: "Διαγραφή",
      confirmDelete: "Διαγραφή slide;",
      cancel: "Ακύρωση",
      save: "Αποθήκευση",
      editTitle: "Επεξεργασία Slide",
      cancelEdit: "Ακύρωση",
      saveSlide: "Αποθήκευση Slide",

      slidebgcolorlabel: " Χρώμα Φόντου",
    }    
  };



  // Set button labels
  document.getElementById('add-slide').textContent = translations[currentLang].addSlide;
  document.getElementById('save-local').textContent = translations[currentLang].saveLocal;
  document.getElementById('load-local').textContent = translations[currentLang].loadLocal;
  <?php if ($use_server): ?>
  document.getElementById('save-server').textContent = translations[currentLang].saveServer;
  document.getElementById('load-server').textContent = translations[currentLang].loadServer;
    <?php endif; ?>

  
  document.getElementById('new-presentation').textContent = translations[currentLang].newPresentation;
  document.getElementById('generate-html').textContent = translations[currentLang].generateHTML;
  
  document.getElementById('slide-bgcolor-label').textContent = translations[currentLang].slidebgcolorlabel;
  document.getElementById('edit-title').textContent = translations[currentLang].editTitle;
  document.getElementById('cancel-edit').textContent = translations[currentLang].cancelEdit;
  document.getElementById('save-slide').textContent = translations[currentLang].saveSlide;

  //document.querySelector('#top-controls button:nth-child(4)').textContent = translations[currentLang].newPresentation;

</script>

<script type="text/javascript">
  const bgColorSelect = document.getElementById('slide-bgcolor');
  colorNames.forEach(color => {
    const option = document.createElement('option');
    option.value = color;
    option.textContent = color;
    bgColorSelect.appendChild(option);
  });

</script>


</body>
</html>