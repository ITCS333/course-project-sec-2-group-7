/*
  Requirement: Make the "Manage Resources" page interactive.

  Instructions:
  1. Link this file to `admin.html` using:
     <script src="admin.js" defer></script>
  
  2. In `admin.html`, add id="resources-tbody" to the <tbody> element
     inside your resources-table. This id is required by this script.
  
  3. Implement the TODOs below.
*/

// --- Global Data Store ---
// This will hold the resources loaded from the API.
let resources = [];

// --- Element Selections ---
// TODO: Select the resource form ('#resource-form').
const resourceForm = document.querySelector('#resource-form');

// TODO: Select the resources table body ('#resources-tbody').
const resourcesTbody = document.querySelector('#resources-tbody');

// --- Functions ---

/**
 * TODO: Implement the createResourceRow function.
 * It takes one resource object { id, title, description, link }.
 * It should return a <tr> element with the following <td>s:
 * 1. A <td> for the title.
 * 2. A <td> for the description.
 * 3. A <td> for the link.
 * 4. A <td> containing two buttons:
 *    - An "Edit" button with class="edit-btn" and data-id="${id}".
 *    - A "Delete" button with class="delete-btn" and data-id="${id}".
 */
function createResourceRow(resource) {
  // Create the table row
  const tr = document.createElement('tr');

  // Create and append the title cell
  const titleTd = document.createElement('td');
  titleTd.textContent = resource.title;
  tr.appendChild(titleTd);

  // Create and append the description cell
  const descriptionTd = document.createElement('td');
  descriptionTd.textContent = resource.description;
  tr.appendChild(descriptionTd);

  // Create and append the link cell
  const linkTd = document.createElement('td');
  linkTd.textContent = resource.link;
  tr.appendChild(linkTd);

  // Create and append the actions cell with Edit and Delete buttons
  const actionsTd = document.createElement('td');

  const editBtn = document.createElement('button');
  editBtn.textContent = 'Edit';
  editBtn.className = 'edit-btn';
  editBtn.dataset.id = resource.id;
  actionsTd.appendChild(editBtn);

  const deleteBtn = document.createElement('button');
  deleteBtn.textContent = 'Delete';
  deleteBtn.className = 'delete-btn';
  deleteBtn.dataset.id = resource.id;
  actionsTd.appendChild(deleteBtn);

  tr.appendChild(actionsTd);

  return tr;
}

/**
 * TODO: Implement the renderTable function.
 * It should:
 * 1. Clear the resources table body ('#resources-tbody').
 * 2. Loop through the global `resources` array.
 * 3. For each resource, call `createResourceRow()` and
 *    append the returned <tr> to the table body.
 */
function renderTable(data) {
  // Use passed-in data if provided, otherwise use global resources array
  const list = Array.isArray(data) ? data : resources;

  // Clear the table body
  resourcesTbody.innerHTML = '';

  // Loop through resources and append each row
  list.forEach(resource => {
    const row = createResourceRow(resource);
    resourcesTbody.appendChild(row);
  });
}

/**
 * TODO: Implement the handleAddResource function.
 * This is the event handler for the form's 'submit' event.
 */
async function handleAddResource(event) {
  // Prevent the form from submitting normally
  event.preventDefault();

  // Get the values from the form fields
  const title = document.querySelector('#resource-title').value.trim();
  const description = document.querySelector('#resource-description').value.trim();
  const link = document.querySelector('#resource-link').value.trim();

  // POST the new resource to the API
  const response = await fetch('./api/index.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ title, description, link })
  });

  // Parse the response
  const result = await response.json();

  // Add the new resource to the global array
  resources.push({ id: result.id, title, description, link });

  // Refresh the table
  renderTable();

  // Reset the form
  resourceForm.reset();
}

/**
 * TODO: Implement the handleTableClick function.
 * This handles click events on the table body using event delegation.
 */
async function handleTableClick(event) {
  const target = event.target;

  // --- Handle Delete ---
  if (target.classList.contains('delete-btn')) {
    // Get the resource id from the button's data-id attribute
    const id = target.dataset.id;

    // DELETE the resource via the API
    await fetch(`./api/index.php?id=${id}`, {
      method: 'DELETE'
    });

    // Remove the resource from the global array
    resources = resources.filter(resource => resource.id != id);

    // Refresh the table
    renderTable();
  }

  // --- Handle Edit ---
  if (target.classList.contains('edit-btn')) {
    // Get the resource id from the button's data-id attribute
    const id = target.dataset.id;

    // Find the matching resource in the global array
    const resource = resources.find(resource => resource.id == id);

    // Populate the form fields with the resource's current values
    document.querySelector('#resource-title').value = resource.title;
    document.querySelector('#resource-description').value = resource.description;
    document.querySelector('#resource-link').value = resource.link;

    // Change the submit button text to indicate edit mode
    const submitBtn = document.querySelector('#add-resource');
    submitBtn.textContent = 'Update Resource';

    // Remove the old submit listener and add update listener
    const handleUpdateResource = async (event) => {
      event.preventDefault();

      // Get the updated values from the form fields
      const title = document.querySelector('#resource-title').value.trim();
      const description = document.querySelector('#resource-description').value.trim();
      const link = document.querySelector('#resource-link').value.trim();

      // PUT the updated resource to the API
      await fetch('./api/index.php', {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id, title, description, link })
      });

      // Update the matching resource in the global array
      resources = resources.map(r =>
        r.id == id ? { id, title, description, link } : r
      );

      // Refresh the table
      renderTable();

      // Reset the form back to Add mode
      resourceForm.reset();
      submitBtn.textContent = 'Add Resource';

      // Restore the original add listener
      resourceForm.removeEventListener('submit', handleUpdateResource);
      resourceForm.addEventListener('submit', handleAddResource);
    };

    // Switch event listeners from Add mode to Update mode
    resourceForm.removeEventListener('submit', handleAddResource);
    resourceForm.addEventListener('submit', handleUpdateResource);
  }
}

/**
 * TODO: Implement the loadAndInitialize function.
 * This function must be 'async'.
 */
async function loadAndInitialize() {
  // Guard to prevent attaching listeners more than once
  if (loadAndInitialize._listenersAttached) return;
  loadAndInitialize._listenersAttached = true;

  // Fetch all resources from the API
  const response = await fetch('./api/index.php');

  // Parse the JSON response
  const result = await response.json();

  // Store the resources in the global array
  resources = result.data;

  // Populate the table for the first time
  renderTable();

  // Add the submit event listener to the form
  resourceForm.addEventListener('submit', handleAddResource);

  // Add the click event listener to the table body
  resourcesTbody.addEventListener('click', handleTableClick);
}

// Initialize the guard flag
loadAndInitialize._listenersAttached = false;

// --- Initial Page Load ---
// Call the main async function to start the application.
loadAndInitialize();