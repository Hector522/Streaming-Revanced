// Theme Switch


let whitemode = localStorage.getItem('whitemode')
const themeSwitch = document.getElementById('theme-switch')

const enableWhitemode = () => {
    document.body.classList.add('whitemode')
    localStorage.setItem('whitemode', 'active')
}

const disableWhitemode = () => {
    document.body.classList.remove('whitemode')
    localStorage.setItem('whitemode', null)
}

if(whitemode === "active") enableWhitemode()

themeSwitch.addEventListener("click", () => {
    whitemode = localStorage.getItem('whitemode')
    whitemode !== "active" ? enableWhitemode() : disableWhitemode ()
})

let count = 0;
let count1 = 0;
let x = 20;
let y = 0;

const maxPlaylists = 12;

function createPlaylist(name = null) {
  const container = document.getElementById("sidebar");

  if (count >= maxPlaylists) {
    alert("Maximum number of playlists reached!");
    return;
  }

  let playlistName = name || prompt("Enter playlist name:");
  if (!playlistName) return;

  const div = document.createElement("div");
  div.className = "playlist";
  div.style.position = "absolute";

  // Position logic
  if (count1 < 2) {
    x = 20 + (count1 * 200);
    y = 100;
  } else if (count1 < 4) {
    x = 20 + ((count1 - 2) * 200);
    y = 300;
  } else if (count1 < 6) {
    x = 20 + ((count1 - 4) * 200);
    y = 500;
  } else if (count1 < 8) {
    x = 20 + ((count1 - 6) * 200);
    y = 700;
  } else if (count1 < 10) {
    x = 20 + ((count1 - 8) * 200);
    y = 900;
  } else if (count1 < 12) {
    x = 20 + ((count1 - 10) * 200);
    y = 1100;
  }

  div.style.left = `${x}px`;
  div.style.top = `${y}px`;

  div.innerHTML = `
    <p style="margin: 5px 0; font-weight: bold;">${playlistName}</p>
    <button onclick="deletePlaylist(${count})" style="margin-top: 5px;">Delete</button>
  `;

  container.appendChild(div);
  savePlaylist(playlistName);

  count++;
  count1++;
}

function savePlaylist(name) {
  let playlists = JSON.parse(localStorage.getItem("playlists")) || [];
  playlists.push(name);
  localStorage.setItem("playlists", JSON.stringify(playlists));
}

function deletePlaylist(index) {
  let playlists = JSON.parse(localStorage.getItem("playlists")) || [];
  playlists.splice(index, 1);
  localStorage.setItem("playlists", JSON.stringify(playlists));
  location.reload(); // simple way to re-render
}

window.onload = function () {
  const saved = JSON.parse(localStorage.getItem("playlists")) || [];
  saved.forEach(name => createPlaylist(name));
};



//TABS
function openTab(evt, action) {
  var i, tabcontent, tablinks;

  // Hide all tab contents
  tabcontent = document.getElementsByClassName("tabcontent");
  for (i = 0; i < tabcontent.length; i++) {
    tabcontent[i].style.display = "none";
  }

  // Remove "active" class from all buttons
  tablinks = document.getElementsByClassName("tablinks");
  for (i = 0; i < tablinks.length; i++) {
    tablinks[i].classList.remove("active");
  }

  // Show the clicked tab and add active class
  document.getElementById(action).style.display = "block";
  evt.currentTarget.classList.add("active");

  // Store the active tab ID in localStorage
  localStorage.setItem("lastTab", action);
}

window.onload = function () {
  const lastTab = localStorage.getItem("lastTab") || "Home";
  const buttonId = "tab" + lastTab.toLowerCase(); // e.g. "tabhome"
  const button = document.getElementById(buttonId);
  if (button) {
    button.click();
  }
};



