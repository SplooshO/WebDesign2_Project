<?php
// members.php - admin-only list of members with live (AJAX) search and delete.
require 'auth.php';
require 'db.php';
require_admin();

$q = trim($_GET['q'] ?? '');
$like = '%' . $q . '%';

$stmt = $pdo->prepare(
    'SELECT member_id, real_name, gamertag, email, program, favorite_game, gaming_platform
     FROM members
     WHERE real_name LIKE ? OR gamertag LIKE ? OR email LIKE ?
        OR program LIKE ? OR favorite_game LIKE ?
     ORDER BY real_name'
);
$stmt->execute([$like, $like, $like, $like, $like]);
$members = $stmt->fetchAll();

$flash = get_flash();
$pageTitle = 'Members';
require 'header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3 gap-2">
    <h1 class="h3 mb-0">Members</h1>
    <a href="member_form.php" class="btn btn-success">
        <i class="bi bi-plus-lg"></i> Add Member
    </a>
</div>

<?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- Search: works without JavaScript (form submit) and live with fetch() -->
<form class="row g-2 mb-3" method="get" action="members.php" id="searchForm">
    <div class="col-12 col-md-6 col-lg-4">
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-search"></i></span>
            <input type="search" id="searchBox" name="q" class="form-control"
                   placeholder="Search by name, gamertag, game..."
                   value="<?= htmlspecialchars($q) ?>" autocomplete="off">
        </div>
    </div>
</form>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-striped table-hover mb-0 align-middle">
            <thead class="table-dark">
                <tr>
                    <th>Name</th>
                    <th>Gamertag</th>
                    <th>Email</th>
                    <th>Program</th>
                    <th>Favourite Game</th>
                    <th>Platform</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody id="memberTableBody">
                <?php if (!$members): ?>
                    <tr><td colspan="7" class="text-center text-muted py-3">No members found.</td></tr>
                <?php endif; ?>

                <?php foreach ($members as $m): ?>
                <tr>
                    <td><?= htmlspecialchars($m['real_name']) ?></td>
                    <td><?= htmlspecialchars($m['gamertag']) ?></td>
                    <td><?= htmlspecialchars($m['email']) ?></td>
                    <td><?= htmlspecialchars($m['program'] ?? '') ?></td>
                    <td><?= htmlspecialchars($m['favorite_game'] ?? '') ?></td>
                    <td><span class="badge text-bg-secondary"><?= htmlspecialchars($m['gaming_platform'] ?? '') ?></span></td>
                    <td class="text-end table-actions">
                        <a href="member_form.php?id=<?= (int)$m['member_id'] ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                        <button type="button" class="btn btn-sm btn-outline-danger"
                                data-bs-toggle="modal" data-bs-target="#deleteModal"
                                data-id="<?= (int)$m['member_id'] ?>"
                                data-name="<?= htmlspecialchars($m['real_name']) ?>">Delete</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Delete confirmation modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="post" action="member_delete.php">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h2 class="modal-title h5" id="deleteModalLabel">Delete member?</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete <strong id="deleteName"></strong>?
                    Their login account and event registrations will be deleted too. This cannot be undone.
                    <input type="hidden" name="member_id" id="deleteId">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// ---- Fill the delete modal with the clicked member ----
document.getElementById('deleteModal').addEventListener('show.bs.modal', function (event) {
    const btn = event.relatedTarget;
    document.getElementById('deleteId').value = btn.getAttribute('data-id');
    document.getElementById('deleteName').textContent = btn.getAttribute('data-name');
});

// ---- AJAX live search using fetch() ----
const searchBox = document.getElementById('searchBox');
const tbody = document.getElementById('memberTableBody');
let timer = null;

// Don't reload the page when Enter is pressed; the live search handles it
document.getElementById('searchForm').addEventListener('submit', function (e) {
    e.preventDefault();
    runSearch();
});

searchBox.addEventListener('input', function () {
    clearTimeout(timer);
    timer = setTimeout(runSearch, 250);   // wait for a pause in typing
});

function runSearch() {
    fetch('search_members.php?q=' + encodeURIComponent(searchBox.value.trim()))
        .then(function (res) {
            if (!res.ok) { throw new Error('Search failed'); }
            return res.json();
        })
        .then(renderRows)
        .catch(function () {
            tbody.innerHTML = '';
            tbody.appendChild(messageRow('Search failed. Please try again.'));
        });
}

function messageRow(text) {
    const tr = document.createElement('tr');
    const td = document.createElement('td');
    td.colSpan = 7;
    td.className = 'text-center text-muted py-3';
    td.textContent = text;
    tr.appendChild(td);
    return tr;
}

function cell(text) {
    const td = document.createElement('td');
    td.textContent = text || '';      // textContent (not innerHTML) keeps this XSS-safe
    return td;
}

function renderRows(members) {
    tbody.innerHTML = '';
    if (members.length === 0) {
        tbody.appendChild(messageRow('No members found.'));
        return;
    }
    members.forEach(function (m) {
        const tr = document.createElement('tr');
        tr.appendChild(cell(m.real_name));
        tr.appendChild(cell(m.gamertag));
        tr.appendChild(cell(m.email));
        tr.appendChild(cell(m.program));
        tr.appendChild(cell(m.favorite_game));

        const platformTd = document.createElement('td');
        const badge = document.createElement('span');
        badge.className = 'badge text-bg-secondary';
        badge.textContent = m.gaming_platform || '';
        platformTd.appendChild(badge);
        tr.appendChild(platformTd);

        const actions = document.createElement('td');
        actions.className = 'text-end table-actions';

        const edit = document.createElement('a');
        edit.href = 'member_form.php?id=' + encodeURIComponent(m.member_id);
        edit.className = 'btn btn-sm btn-outline-primary';
        edit.textContent = 'Edit';

        const del = document.createElement('button');
        del.type = 'button';
        del.className = 'btn btn-sm btn-outline-danger';
        del.textContent = 'Delete';
        del.setAttribute('data-bs-toggle', 'modal');
        del.setAttribute('data-bs-target', '#deleteModal');
        del.setAttribute('data-id', m.member_id);
        del.setAttribute('data-name', m.real_name);

        actions.appendChild(edit);
        actions.appendChild(del);
        tr.appendChild(actions);
        tbody.appendChild(tr);
    });
}
</script>

<?php require 'footer.php'; ?>
