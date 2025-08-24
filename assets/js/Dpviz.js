$(document).ready(function() {
	//github update check
	$('#check-update-btn').click(function() {
		$('#update-result').html(`<div style="margin-top: 10px;">${translations.checking}</div>`);

		$.ajax({
			url: 'ajax.php?module=dpviz&command=check_update',
			method: 'POST',
			dataType: 'json',
			
			success: function(response) {
				if (response.status === 'success') {
					if (response.up_to_date) {
						$('#update-result').html(`<div style="margin-top: 10px;">${translations.uptodate}</div>`);
					} else {
						$('#update-result').html(
							`<a href="config.php?display=modules" target="_blank" class="btn btn-default">
								 ${response.latest} ${translations.available}
								 <i class="fa fa-external-link" aria-hidden="true"></i>
							 </a> 
							 ${translations.currentVersion}: ${response.current}`
						);
					}
				} else {
						$('#update-result').html('Error: ' + response.message);
				}
			},
			error: function(xhr, status, error) {
					$('#update-result').html('AJAX error: ' + error);
			}
		});
	});

});

//Save Setting, then show Dial Plan tab.
$('#dpvizForm').submit(function(event) {
	event.preventDefault(); 

	var $form = $(this);
	var formData = $form.serialize();
	var processed = document.getElementById('processed')?.value || '';
	var ext = document.getElementById('ext')?.value || '';
	var jump = document.getElementById('jump')?.value || '';
	var skip = [];
	var pan = $form.find('input[name="panzoom"]:checked').val();
	try {
		const raw = document.getElementById('skip')?.value?.trim() || '[]';
		skip = JSON.parse(raw);
	} catch (e) {
		console.error("Invalid skip array", e);
	}

	$.ajax({
		type: 'POST',
		url: $form.attr('action'),
		data: formData,
		success: function(response) {
			var saveButton = document.getElementById("saveButton");
			const savedText = saveButton.dataset.savedLabel;
			var originalContent = saveButton.innerHTML;
		
			saveButton.innerHTML = '<i class="fa fa-check"></i> ' + savedText;
			
			setTimeout(function() {
				if (processed === 'yes') {
					generateVisualization(ext,jump,skip,pan);
				}
				saveButton.innerHTML = originalContent;
				$('.nav-tabs li[data-name="dpbox"] a').tab('show'); // Switch tab
			}, 1250);
			
		},
		error: function(error) {
			alert('Form submission failed: ' + error.statusText);
			document.getElementById('saveResponse').textContent = "Request failed.";
		}
	});
});

//reload button
$('#reloadButton').click(function() {
	var ext = document.getElementById('ext')?.value || '';
	var jump = document.getElementById('jump')?.value || '';
	var pan = document.getElementById('panzoom')?.value || '';
	
	var skip = [];
	try {
		const raw = document.getElementById('skip')?.value?.trim() || '[]';
		skip = JSON.parse(raw);
	} catch (e) {
		console.error("Invalid skip array", e);
	}
	
	resetFocusMode();
	generateVisualization(ext,jump,skip,pan);
});


function generateVisualization(ext, jump, skips, pan) {	
	const vizContainer = document.getElementById("vizContainer");
	const spinner = document.getElementById("vizSpinner");
	const modal = document.getElementById('recordingmodal');
	const overlay = document.getElementById('overlay');
	const vizHeader = document.getElementById('vizHeader');
	const vizGraph = document.getElementById('vizGraph');
	const toggleButton = document.getElementById("append");
	const sanitizeBtn = document.getElementById("sanitizeBtn");
	const header = document.getElementById("headerSelected");
	

	
	skips = skips || [];
	
	closeModal();
	//console.log("Skips:", skips.join(", "));
	
	spinner.style.display = "flex";
  $.ajax({
    url: 'ajax.php?module=dpviz&command=make',
    type: 'POST',
    data: JSON.stringify({
			ext: ext,
			jump: jump,
			skip: skips
		}),
		
    dataType: 'json',
    success: function(response) {
			const saveButton = document.getElementById('saveModalBtn');
			if ((jump && jump.trim() !== '') || skips.length > 0) {
				saveButton.style.display = 'block';
				//saveButton.disabled = false;
			} else {
				saveButton.style.display = 'none';
				//saveButton.disabled = true;
			}
			
			/*
			if (!toggleButton.classList.contains("active")) {
				vizGraph.innerHTML = ""; // clear the container if button is NOT active
			}
			*/
	
      $('#vizHeader').html(response.vizHeader);
			vizGraph.innerHTML = "";
      if (response.gtext) {
				//console.log(response.gtext);
				let dot = response.gtext
					//.replace(/\"/g, '\"')
					//.replace(/\\n/g, '\n')
				.replace(/\\l/g, '\l')
					;
				

				viz.renderSVGElement(dot)
					.then(function(element) {
						isFocused = false;
						isSanitized = false;
            svgContainer = element;
						
            vizGraph.appendChild(element);
						spinner.style.display = "none";  //hide spinner

            if (pan === "1") {
							const innerGroup = element.querySelector('g'); // first <g> inside <svg>
							if (innerGroup) {
								panzoom(innerGroup, {
									zoomDoubleClickSpeed: 1,
								});
							} else {
								console.warn("Could not find inner <g> element for panzoom.");
							}
						}
						
						// Ctrl/Command + shift + click handler for Graphviz nodes
						element.querySelectorAll('g.node').forEach(node => {
							
							node.addEventListener('click', function (e) {
								const titleElement = node.querySelector('title');
								if (!titleElement) return;

								const titleText = titleElement.textContent || titleElement.innerText || "";

								// Check for "Play Recording:" pattern
								if (titleText.startsWith("play-system-recording")) {
									e.preventDefault();
									
									
									if (modal && overlay && !isFocused && !isSanitized && !e.ctrlKey && !e.metaKey && !e.shiftKey) {
										//overlay.style.display = 'block';
										spinner.style.display = "flex";
										getRecording(titleText);
										
										setTimeout(() => {
											spinner.style.display = "none";
											modal.style.display = 'block';
										}, 500);
									}
								}
								
								if (titleText.startsWith("reset") && !isFocused && !isSanitized) {
									e.preventDefault();
									resetFocusMode();
									generateVisualization(ext,'','',pan);
								}

								if (titleText.startsWith("undoLast") && !isFocused && !isSanitized) {
										e.preventDefault();
										resetFocusMode();

										const toRemove = titleText.replace("undoLast", "").trim();

										const index = skips.indexOf(toRemove);
										if (index !== -1) {
												skips.splice(index, 1);
										}

										generateVisualization(ext,jump,skips, pan);
								}
								
								// Ctrl/Meta -jump
								if ((e.ctrlKey || e.metaKey) && !isFocused && !isSanitized) {
									e.preventDefault();
									resetFocusMode();
									
									generateVisualization(ext,titleText,skips,pan);
								}
								
								// Shift Key -skip(s)
								if (e.shiftKey && !isFocused && !isSanitized) {
									e.preventDefault();
									const allowedKeywords = ["announcement","callback","callrecording","daynight","directory","dynroute","ext-group","ext-tts","from-trunk",
										"ivr","languages","miscapp","queueprio","queues","vqueues","setcid","timeconditions","vmblast-grp"];

									const match = allowedKeywords.find(keyword =>
											titleText.toLowerCase().includes(keyword.toLowerCase())
									);

									if (!match) {
											return;
									}

									if (!skips.includes(titleText)) {
											skips.push(titleText);
											resetFocusMode();

											generateVisualization(ext,jump,skips,pan);
									}
								}

							});
							const text = node.querySelector('text');
							if (text && text.textContent.trim() === '+') {
								const link = node.querySelector('a');
								if (link) {
									link.style.textDecoration = 'none';
								}
							}
						});

            element.querySelectorAll("g.node").forEach(node => {
              node.addEventListener("click", function(e) {
                if (isFocused) {
                  selectedNodeId = this.id;
                  highlightPathToNode(this.id);
                  e.preventDefault();
                  e.stopPropagation();
                  return false;
                }
              });
            });

            element.querySelectorAll("g.edge").forEach(edge => {
              edge.addEventListener("click", function(e) {
                if (isFocused) {
                  toggleEdgeHighlight(this.id);
                  e.preventDefault();
                  e.stopPropagation();
                  return false;
                }
              });
            });
						
						// keep highlight for just one path
						document.querySelectorAll("g.edge").forEach(edge => {
							edge.addEventListener("click", (e) => {
								// clear other selections unless you want multi-select
								document.querySelectorAll("g.edge.selected").forEach(el => {
									if (el !== edge) el.classList.remove("selected");
								});

								// toggle this one
								edge.classList.toggle("selected");

								e.stopPropagation(); // don’t bubble up to SVG container
							});
						});

						// --- sanitize setup ---
						const sanitizeBtn = document.getElementById("sanitizeBtn");
						let originalFilename = "";

						// Reset any previous sanitize state first
						resetSanitize();

						// Only bind the master button once
						if (!sanitizeBtn._bound) {
    sanitizeBtn.addEventListener("click", () => {
        const texts = document.querySelectorAll("g.node text");
        const header = document.getElementById("headerSelected");
        const input = document.getElementById("filenameInput");
        const version = document.getElementById("version");

        if (!isSanitized) {
            // ENTER sanitize mode
            originalFilename = input.value; // store filename
            disableLinks();
            input.value = "";
            input.placeholder = "Enter filename...";
            version.style.display = "flex";

            document.querySelectorAll("g.node a").forEach(link => {
                link.addEventListener("click", e => {
                    if (isSanitized) e.preventDefault();
                });
            });

            // Black out all labels
            texts.forEach(t => censor(t));

            if (header) {
                delete header.dataset.censored;
                delete header.dataset.prevColor;
                delete header.dataset.prevBg;
                censor(header);
            }

            setSanitizeButton("restore");

        } else {
            // EXIT sanitize mode
            input.value = originalFilename; // restore filename
            version.style.display = "none";
            texts.forEach(t => uncensor(t));

            if (header) uncensor(header);

            restoreLinks();
            setSanitizeButton("sanitize");
        }

        isSanitized = !isSanitized;
    });

    // 🔹 GLOBAL DELEGATION: Handle clicks on nodes + header
    document.addEventListener("click", e => {
        if (!isSanitized) return;

        // Node labels
        if (e.target.closest("g.node")) {
            e.stopPropagation();
            e.target.closest("g.node").querySelectorAll("text").forEach(t => toggleCensor(t));
        }

        // Header
        const header = document.getElementById("headerSelected");
        if (header && e.target === header) {
            e.stopPropagation();
            toggleCensor(header);
        }
    });

    sanitizeBtn._bound = true; // prevent duplicate bindings
}




						//end sanitize

          })
          .catch(error => {
            console.error('Viz.js render error:', error);
          });
      } else {
        console.error('No gtext found in response.');
      }
    },
    error: function(xhr, status, error) {
			spinner.style.display = "none";  // Hide spinner

			const errorMsg = `
					<strong>AJAX Error:</strong><br>
					Status: ${status}<br>
					Error: ${error}<br>
					HTTP Status: ${xhr.status}<br>
					Response: ${xhr.responseText}
			`;

			$('#vizContainer').html(errorMsg);
			console.error('AJAX Error:', status, error);
		}
  });
	
}


function getRecording(titleid) {	
	const parts = titleid.split(",");
	const id = parts[1];
	const other = parts[2];
	const lang = parts[3];

	const formData = new URLSearchParams();
	formData.append('id', id);
	formData.append('lang', lang);

	fetch('ajax.php?module=dpviz&command=getrecording', {
		method: 'POST',
		headers: {
			'Content-Type': 'application/x-www-form-urlencoded'
		},
		body: formData
	})
	.then(response => {
		if (!response.ok) throw new Error("Failed to load recording info");
		return response.json();
	})
	.then(async data => {
		//console.log("Display name:", data.displayname);
		console.log("Filename(s):", data.filename);

		const displayname = data.displayname;
		const audioList = document.getElementById('audioList');
		audioList.innerHTML = "";

		$('#recording-displayname').html(
			'<a href="config.php?display=recordings&action=edit&id=' + id + '" target="_blank" style="width:100%" class="btn btn-default btn-lg">' +
			'<i class="fa fa-bullhorn"></i> ' + translations.recordingLabel + ': ' + displayname +
			' <i class="fa fa-external-link" aria-hidden="true"></i></a>'
		);
		
		if (!data.filename || data.filename.trim() === '') {
			throw new Error(`${translations.noFilesLang} <strong>${lang}</strong>`);
		}
		
		const filenames = data.filename.split('&').filter(f => f.trim() !== '');
		if (filenames.length === 0) {
			throw new Error("Filename array is empty after parsing.");
		}
		
		const audioElements = []; // keep all audio tags

		for (const filename of filenames) {
			console.log("Fetching file:", filename);

			try {
				const response = await fetch('ajax.php?module=dpviz&command=getfile', {
					method: 'POST',
					headers: {
						'Content-Type': 'application/x-www-form-urlencoded'
					},
					body: `file=${encodeURIComponent(filename)}`
				});

				if (!response.ok) {
					throw new Error(`Could not fetch ${filename}`);
				}

				const blob = await response.blob();
				const headerFilename = response.headers.get('X-Filename');
				const audioUrl = URL.createObjectURL(blob);

				const container = document.createElement('div');
				container.classList.add('card', 'mb-3', 'custom-card-bg');

				const cardBody = document.createElement('div');
				cardBody.classList.add('card-body');

				const shortFilename = headerFilename.split('/').pop();

				const cardTitle = document.createElement('h5');
				cardTitle.classList.add('card-title', 'text-left');

				// Create span for text
				const titleText = document.createElement('span');
				titleText.textContent = `${translations.audioLabel}: ${headerFilename}.wav`;

				// Create copy button
				const copyBtn = document.createElement('button');
				copyBtn.classList.add('btn', 'btn-sm', 'btn-outline-secondary');
				copyBtn.innerHTML = '  <i class="fa fa-copy"></i>';
				copyBtn.title = translations.copyFilename;
				copyBtn.style.marginLeft = '10px';

				// Handle copy to clipboard
				copyBtn.addEventListener('click', () => {
					navigator.clipboard.writeText(shortFilename + '.wav')
						.then(() => {
							copyBtn.innerHTML = '  <i class="fa fa-check"></i>';
							setTimeout(() => {
								copyBtn.innerHTML = '  <i class="fa fa-copy"></i>';
							}, 1500);
						})
						.catch(err => {
							console.error('Copy failed:', err);
						});
				});

				// Append text and button to the card title
				cardTitle.appendChild(titleText);
				cardTitle.appendChild(copyBtn);

				// Append to card body
				cardBody.appendChild(cardTitle);

				const audio = document.createElement('audio');
				audio.controls = true;
				audio.src = audioUrl;
				cardBody.appendChild(audio);

				container.appendChild(cardBody);
				audioList.appendChild(container);

				audioElements.push(audio); // store audio
			} catch (err) {
				const container = document.createElement('div');
				container.classList.add('recording-container', 'error');

				const label = document.createElement('div');
				label.classList.add('alert', 'alert-warning');
				label.innerHTML = `File: <strong>${filename}.wav</strong> ${translations.fileNotFound}`;

				container.appendChild(label);
				audioList.appendChild(container);
			}
		}

		// Chain audio playback
		audioElements.forEach((audio, index) => {
			if (index < audioElements.length - 1) {
				audio.addEventListener('ended', () => {
					audioElements[index + 1].play().catch(err => {
						console.log("Next playback blocked:", err);
					});
				});
			}
		});

		// Autoplay the first one
		if (audioElements.length > 0) {
			setTimeout(() => {
				audioElements[0].play().catch(err => {
					console.log("Playback blocked:", err);
				});
			}, 500); // delay in ms (adjust as needed)
		}
	})
	.catch(err => {
		console.error("Fetch error:", err);

		const audioList = document.getElementById('audioList');

		const container = document.createElement('div');
		container.classList.add('recording-container', 'error');

		const label = document.createElement('div');
		label.classList.add('alert', 'alert-danger');
		label.innerHTML = `<strong>Error:</strong> ${err.message}`;

		container.appendChild(label);
		audioList.appendChild(container);
	});
}



document.addEventListener('play', function(e) {
  const audios = document.querySelectorAll('audio');
  audios.forEach(audio => {
    if (audio !== e.target) {
      audio.pause();
    }
  });
}, true);

document.addEventListener('keydown', function(event) {
  if (event.key === 'Escape') {
    const modal = document.getElementById('recordingmodal');
		const savemodal = document.getElementById('saveModal');
    if (modal && modal.style.display !== 'none') {
      closeModal();
    }
		if (savemodal && savemodal.style.display !== 'none') {
      closeSaveModal();
    }
  }
});

function closeModal() {
  const modal = document.getElementById('recordingmodal');
  const overlay = document.getElementById('overlay');
  modal.style.display = 'none';
  overlay.style.display = 'none';

  // Stop and reset all audio elements in the document
  const allAudio = document.querySelectorAll('audio');
  allAudio.forEach(audio => {
    audio.pause();
    audio.currentTime = 0;
  });
}

const modal = document.getElementById("recordingmodal");
const header = document.getElementById("recordingmodal-header");

let isDragging = false;
let offsetX = 0;
let offsetY = 0;

header.addEventListener("mousedown", (e) => {
	isDragging = true;
	offsetX = e.clientX - modal.offsetLeft;
	offsetY = e.clientY - modal.offsetTop;   
	document.body.style.userSelect = 'none';
});

document.addEventListener("mouseup", () => {
	isDragging = false;
	document.body.style.userSelect = 'auto';
});

document.addEventListener("mousemove", (e) => {
	if (isDragging) {
		modal.style.left = (e.clientX - offsetX) + "px";
		modal.style.top = (e.clientY - offsetY) + "px";
	}
});


//saved view modal
document.getElementById('saveModalBtn').addEventListener('click', function () {
	const viewId = document.getElementById('viewId')?.value.trim() || '';
	const deleteBtn = document.getElementById('deleteViewBtn');
  if (viewId) {
    deleteBtn.style.display = 'inline-block';
  } else {
    deleteBtn.style.display = 'none';
  }
  document.getElementById('saveModal').style.display = 'block';
});

window.addEventListener('click', function (e) {
  const modal = document.getElementById('saveModal');
  if (e.target === modal) {
    modal.style.display = 'none';
  }
});

function closeSaveModal() {
  document.getElementById('saveModal').style.display = 'none';
}

//save / delete views
document.getElementById('saveViewForm').addEventListener('submit', function (e) {
  e.preventDefault();
	
	const id = document.getElementById('viewId')?.value.trim() || '';
  const description = document.getElementById('savedDescription').value;
	const ext = document.getElementById('ext')?.value.trim() || '';
	const jump = document.getElementById('jump')?.value.trim() || '';
	const skip = document.getElementById('skip')?.value.trim() || '';
	
  const data = {
		id: id,
    description: description,
    ext: ext,
    jump: jump,
    skip: skip // array
  };

	$.ajax({
		type: 'POST',
		url: 'ajax.php?module=dpviz&command=saveview',
		data: data,
		success: function (response) {
			fpbxToast(`${translations.viewSaved}`,'Saving','success');
			$('#saveModal').hide();
			$('#description').val('');

			setTimeout(function () {
				location.reload();
			}, 2000);
			console.log('Response:', response);
		},
		error: function (xhr, status, error) {
			alert('Error saving view.');
			console.error('AJAX Error:', error);
		}
	});

  // Close modal after submit
  document.getElementById('saveModal').style.display = 'none';
});

document.getElementById('deleteViewBtn').addEventListener('click', function () {
	const viewId = document.getElementById('viewId')?.value.trim() || '';
	
	$.ajax({
		type: 'POST',
		url: 'ajax.php?module=dpviz&command=deleteview',
		data: { id: viewId },
		success: function (response) {
			
			fpbxToast(`${translations.viewDeleted}`,'Saving','success');
			$('#saveModal').hide();
			$('#description').val('');

			setTimeout(function () {
				location.reload();
			}, 2000);
			console.log('Response:', response);
		},
		error: function (xhr, status, error) {
			alert('Error saving view.');
			console.error('AJAX Error:', error);
		}
	});
	
});


// ----- censor helpers -----
function censor(el) {
  if (el instanceof SVGTextElement) {
    if (el.dataset.censored) return;
    const bbox = el.getBBox();
    const rect = document.createElementNS("http://www.w3.org/2000/svg", "rect");
    rect.setAttribute("x", bbox.x);
    rect.setAttribute("y", bbox.y - 1);
    rect.setAttribute("width", bbox.width);
    rect.setAttribute("height", bbox.height + 2);
    rect.setAttribute("fill", "black");
    rect.classList.add("censor-bar");
    el.parentNode.insertBefore(rect, el);
    el.dataset.censored = "true";
  } else if (el instanceof HTMLElement) {
    if (el.dataset.censored) return;

    // Save original inline styles before overwriting
    el.dataset.prevColor = el.style.color || "";
    el.dataset.prevBg = el.style.backgroundColor || "";

    el.style.backgroundColor = "black";
    el.style.color = "black";
    el.dataset.censored = "true";
  }
}

function uncensor(el) {
  if (el instanceof SVGTextElement) {
    el.parentNode.querySelectorAll(".censor-bar").forEach(r => r.remove());
    delete el.dataset.censored;
  } else if (el instanceof HTMLElement) {
    // Restore original styles if we saved them
    if (el.dataset.prevColor !== undefined) {
      el.style.color = el.dataset.prevColor;
      delete el.dataset.prevColor;
    } else {
      el.style.color = "";
    }

    if (el.dataset.prevBg !== undefined) {
      el.style.backgroundColor = el.dataset.prevBg;
      delete el.dataset.prevBg;
    } else {
      el.style.backgroundColor = "";
    }

    delete el.dataset.censored;
		
  }
}


function toggleCensor(el) {
  if (!el) return;
  if (!el.dataset.censored) {
    censor(el);
  } else {
    uncensor(el);
  }
}



// ----- resetSanitize -----
function resetSanitize() {
  const texts = document.querySelectorAll("g.node text");
  const header = document.getElementById("headerSelected");

  // Remove all blackouts from node texts
  texts.forEach(t => uncensor(t));

  // Explicitly reset the header, even if dataset flags are stuck
  if (header) {
    header.style.color = "";
    header.style.backgroundColor = "";
    delete header.dataset.censored;
    delete header.dataset.prevColor;
    delete header.dataset.prevBg;
  }

  // Restore links
  restoreLinks();

  // Reset button text and global state
  setSanitizeButton("sanitize");
  isSanitized = false;

  // Remove per-node toggle listeners safely
  if (sanitizeBtn._nodeToggleBound) {
    document.querySelectorAll("g.node").forEach(node => {
      const text = node.querySelector("text");
      if (text) {
        const newText = text.cloneNode(true);
        text.parentNode.replaceChild(newText, text);
      }
    });
    sanitizeBtn._nodeToggleBound = false;
  }
}




function setSanitizeButton(state) {
  if (state === "sanitize") {
    sanitizeBtn.innerHTML = '<i class="fa fa-eye-slash"></i> ' + translations.sanitizeLabels;
    sanitizeBtn.classList.add("btn-default");
    sanitizeBtn.classList.remove("btn-primary", "active");
  } else if (state === "restore") {
    sanitizeBtn.innerHTML = '<i class="fa fa-eye"></i> ' + translations.restoreLabels;
    sanitizeBtn.classList.remove("btn-default");
    sanitizeBtn.classList.add("btn-primary", "active");
  }
}


//hamburger menu
const hamburger = document.getElementById("hamburgerBtn");
const dropdown = document.getElementById("dropdownMenu");

hamburger.addEventListener("click", () => {
	dropdown.style.display = dropdown.style.display === "block" ? "none" : "block";
});

// Optional: click outside to close
document.addEventListener("click", (e) => {
	if (!hamburger.contains(e.target) && !dropdown.contains(e.target)) {
		dropdown.style.display = "none";
	}
});