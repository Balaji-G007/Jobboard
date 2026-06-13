// assets/js/search.js
// Live AJAX search for jobs.php

document.addEventListener('DOMContentLoaded', () => {

  const qInput    = document.getElementById('searchQ');
  const locInput  = document.getElementById('searchLoc');
  const typeSelect= document.getElementById('searchType');
  const jobsList  = document.getElementById('jobsList');
  const countEl   = document.getElementById('resultsCount');

  // Only run on pages with the search elements
  if (!qInput || !jobsList) return;

  let debounceTimer = null;

  function doSearch() {
    const q    = qInput?.value.trim()    || '';
    const loc  = locInput?.value.trim()  || '';
    const type = typeSelect?.value       || '';

    const params = new URLSearchParams();
    if (q)    params.set('q',    q);
    if (loc)  params.set('loc',  loc);
    if (type) params.set('type', type);

    // Show loading skeleton
    jobsList.innerHTML = `
      <div style="padding:40px;text-align:center;color:var(--muted)">
        <div style="font-size:1.5rem;margin-bottom:12px;animation:spin 1s linear infinite;display:inline-block">⏳</div><br>
        Searching jobs...
      </div>`;

    fetch('/ajax/search_jobs.php?' + params.toString())
      .then(r => r.json())
      .then(data => {
        if (countEl) countEl.textContent = `${data.count} job${data.count !== 1 ? 's' : ''} found`;

        if (data.count === 0) {
          jobsList.innerHTML = `
            <div class="empty-state">
              <div style="font-size:2.5rem;margin-bottom:12px">🔍</div>
              No jobs match your search.<br>
              <a href="/jobs.php" style="color:var(--accent);font-weight:600">Clear filters</a>
            </div>`;
          return;
        }

        jobsList.innerHTML = data.jobs.map(job => `
          <a href="/job_detail.php?id=${job.id}" class="job-row-card">
            <div class="job-row-left">
              <h3>${escHtml(job.title)}</h3>
              <div class="company">🏢 ${escHtml(job.company)}</div>
              <div class="tags">
                <span class="tag tag-loc">📍 ${escHtml(job.location)}</span>
                <span class="tag tag-type">${escHtml(job.type)}</span>
                ${job.salary ? `<span class="tag tag-sal">💰 ${escHtml(job.salary)}</span>` : ''}
              </div>
            </div>
            <div class="job-row-right">
              <span style="font-size:.78rem;color:var(--muted)">${job.posted}</span>
              <span class="btn-primary btn-sm">View →</span>
            </div>
          </a>
        `).join('');
      })
      .catch(() => {
        jobsList.innerHTML = `<div class="alert alert-error">Search failed. Please try again.</div>`;
      });
  }

  function escHtml(str) {
    const d = document.createElement('div');
    d.textContent = str || '';
    return d.innerHTML;
  }

  // Debounced listeners
  [qInput, locInput].forEach(el => {
    if (!el) return;
    el.addEventListener('input', () => {
      clearTimeout(debounceTimer);
      debounceTimer = setTimeout(doSearch, 350);
    });
  });

  if (typeSelect) {
    typeSelect.addEventListener('change', doSearch);
  }

  // Prevent form submit (we handle it via AJAX)
  const filterForm = document.getElementById('filterForm');
  if (filterForm) {
    filterForm.addEventListener('submit', e => {
      e.preventDefault();
      doSearch();
    });
  }
});