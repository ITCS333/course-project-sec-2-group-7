/*
  Requirement: Populate the assignment detail page and discussion forum.

  Instructions:
  1. This file is already linked to `details.html` via:
         <script src="details.js" defer></script>

  2. The following ids must exist in details.html (already listed in the
     HTML comments):
       #assignment-title       — <h1>
       #assignment-due-date    — <p>
       #assignment-description — <p>
       #assignment-files-list  — <ul>
       #comment-list           — <div>
       #comment-form           — <form>
       #new-comment            — <textarea>

  3. Implement the TODOs below.

  API base URL: ./api/index.php
  Assignment object shape returned by the API:
    {
      id:          number,   // integer primary key from the assignments table
      title:       string,
      due_date:    string,   // "YYYY-MM-DD" — matches the SQL column name
      description: string,
      files:       string[]  // decoded array of URL strings
    }

  Comment object shape returned by the API
  (from the comments_assignment table):
    {
      id:            number,
      assignment_id: number,
      author:        string,
      text:          string,
      created_at:    string
    }
*/

// --- Global Data Store ---
let currentAssignmentId = null;
let currentComments = [];

// --- Element Selections ---
// TODO: Select each element by its id:
//   assignmentTitle, assignmentDueDate, assignmentDescription,
//   assignmentFilesList, commentList, commentForm, newCommentInput.
const assignmentTitle = document.querySelector('#assignment-title');
const assignmentDueDate = document.querySelector('#assignment-due-date');
const assignmentDescription = document.querySelector('#assignment-description');
const assignmentFilesList = document.querySelector('#assignment-files-list');
const commentList = document.querySelector('#comment-list');
const commentForm = document.querySelector('#comment-form');
const newCommentInput = document.querySelector('#new-comment');

// --- Functions ---

/**
 * TODO: Implement getAssignmentIdFromURL.
 *
 * It should:
 * 1. Read window.location.search.
 * 2. Construct a URLSearchParams object from it.
 * 3. Return the value of the 'id' parameter (a string that represents
 *    the integer primary key of the assignment).
 */
function getAssignmentIdFromURL() {
  // Get the query string from the URL
  const params = new URLSearchParams(window.location.search);

  // Return the value of the 'id' parameter
  return params.get('id');
}

/**
 * TODO: Implement renderAssignmentDetails.
 *
 * Parameters:
 *   assignment — the assignment object returned by the API (see shape above).
 *
 * It should:
 * 1. Set assignmentTitle.textContent       = assignment.title.
 * 2. Set assignmentDueDate.textContent     = "Due: " + assignment.due_date.
 *    (Note: use assignment.due_date, which matches the SQL column name.)
 * 3. Set assignmentDescription.textContent = assignment.description.
 * 4. Clear assignmentFilesList, then for each URL in assignment.files:
 *    - Create a <li> containing an <a href="{url}">{url}</a>.
 *    - Append the <li> to assignmentFilesList.
 *    (assignment.files is already a decoded string array from the API.)
 */
function renderAssignmentDetails(assignment) {
  // Set the title text
  assignmentTitle.textContent = assignment.title;

  // Set the due date text with "Due: " prefix
  assignmentDueDate.textContent = 'Due: ' + assignment.due_date;

  // Set the description text
  assignmentDescription.textContent = assignment.description;

  // Clear the files list
  assignmentFilesList.innerHTML = '';

  // Guard against undefined or missing files
  const files = assignment.files || [];
  files.forEach(url => {
    const li = document.createElement('li');
    const a = document.createElement('a');
    a.href = url;
    a.textContent = url;
    li.appendChild(a);
    assignmentFilesList.appendChild(li);
  });
}

/**
 * TODO: Implement createCommentArticle.
 *
 * Parameters:
 *   comment — one comment object from the API:
 *     { id, assignment_id, author, text, created_at }
 *
 * Returns an <article> element:
 *   <article>
 *     <p>{comment.text}</p>
 *     <footer>Posted by: {comment.author}</footer>
 *   </article>
 */
function createCommentArticle(comment) {
  // Create the article element
  const article = document.createElement('article');

  // Create and append the comment text paragraph
  const text = document.createElement('p');
  text.textContent = comment.text;
  article.appendChild(text);

  // Create and append the author footer
  const footer = document.createElement('footer');
  footer.textContent = `Posted by: ${comment.author}`;
  article.appendChild(footer);

  return article;
}

/**
 * TODO: Implement renderComments.
 *
 * It should:
 * 1. Clear commentList (set innerHTML to "").
 * 2. Loop through currentComments.
 * 3. For each comment, call createCommentArticle(comment) and
 *    append the result to commentList.
 */
function renderComments() {
  // Clear the comment list
  commentList.innerHTML = '';

  // Loop through comments and append each article
  currentComments.forEach(comment => {
    const article = createCommentArticle(comment);
    commentList.appendChild(article);
  });
}

/**
 * TODO: Implement handleAddComment (async).
 *
 * This is the event handler for commentForm's 'submit' event.
 * It should:
 * 1. Call event.preventDefault().
 * 2. Read and trim the value from newCommentInput (#new-comment).
 * 3. If the value is empty, return early (do nothing).
 * 4. Send a POST to './api/index.php?action=comment' with the body:
 *      {
 *        assignment_id: currentAssignmentId,   // integer
 *        author:        "Student",             // hardcoded for this exercise
 *        text:          commentText
 *      }
 *    The API inserts a row into the comments_assignment table.
 * 5. On success (result.success === true):
 *    - Push the new comment object (from result.data) onto
 *      currentComments.
 *    - Call renderComments() to refresh the list.
 *    - Clear newCommentInput.
 */
async function handleAddComment(event) {
  // Prevent the form from submitting normally
  event.preventDefault();

  // Read and trim the comment text
  const commentText = newCommentInput.value.trim();

  // If the text is empty, return early
  if (!commentText) return;

  // POST the new comment to the API
  const response = await fetch('./api/index.php?action=comment', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      assignment_id: currentAssignmentId,
      author: 'Student',
      text: commentText
    })
  });

  // Parse the response
  const result = await response.json();

  // On success, update the comments array and re-render
  if (result.success === true) {
    currentComments.push(result.data);
    renderComments();
    newCommentInput.value = '';
  }
}

/**
 * TODO: Implement initializePage (async).
 *
 * It should:
 * 1. Call getAssignmentIdFromURL() and store the result in
 *    currentAssignmentId.
 * 2. If currentAssignmentId is null or empty, set
 *    assignmentTitle.textContent = "Assignment not found." and return.
 * 3. Fetch both the assignment details and its comments in parallel using
 *    Promise.all:
 *      - Assignment: GET ./api/index.php?id={currentAssignmentId}
 *                    Response: { success: true, data: { ...assignment object } }
 *      - Comments:   GET ./api/index.php?action=comments&assignment_id={currentAssignmentId}
 *                    Response: { success: true, data: [ ...comment objects ] }
 *    Comments are stored in the comments_assignment table
 *    (columns: id, assignment_id, author, text, created_at).
 * 4. Store the comments array in currentComments
 *    (use an empty array if none exist).
 * 5. If the assignment was found:
 *    - Call renderAssignmentDetails(assignment).
 *    - Call renderComments().
 *    - Attach the 'submit' listener to commentForm (calls handleAddComment).
 * 6. If the assignment was not found:
 *    - Set assignmentTitle.textContent = "Assignment not found."
 */
async function initializePage() {
  // Get the assignment id from the URL
  currentAssignmentId = getAssignmentIdFromURL();

  // If no id found, show error and stop
  if (!currentAssignmentId) {
    assignmentTitle.textContent = 'Assignment not found.';
    return;
  }

  // Fetch assignment details and comments at the same time
  const [assignmentResponse, commentsResponse] = await Promise.all([
    fetch(`./api/index.php?id=${currentAssignmentId}`),
    fetch(`./api/index.php?action=comments&assignment_id=${currentAssignmentId}`)
  ]);

  // Parse both responses
  const assignmentResult = await assignmentResponse.json();
  const commentsResult = await commentsResponse.json();

  // Store the comments in the global array
  currentComments = commentsResult.data || [];

  // Guard: data must be a non-null object and not an array
  const assignment = assignmentResult.data;
  if (assignmentResult.success && assignment && !Array.isArray(assignment)) {
    renderAssignmentDetails(assignment);
    renderComments();
    commentForm.addEventListener('submit', handleAddComment);
  } else {
    // If assignment not found, show error
    assignmentTitle.textContent = 'Assignment not found.';
  }
}

// --- Initial Page Load ---
initializePage();