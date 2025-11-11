
<?php
require_once 'includes/ubbc-functions.php';
$link = connect();

$perPage = 50;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $perPage;

$order = 'time';
$dir = 'DESC';
$bibFilter = null;

if (!empty($_GET['order']) && in_array($_GET['order'], ['time', 'bib'])) {
    $order = $_GET['order'];
}
if (!empty($_GET['dir']) && in_array($_GET['dir'], ['ASC', 'DESC'])) {
    $dir = $_GET['dir'];
}
if (!empty($_GET['bib'])) {
    $bibFilter = intval($_GET['bib']);
}

$where = '';
if ($bibFilter) {
    $where = "WHERE users.bib = $bibFilter";
}

$countQuery = "
    SELECT COUNT(*) as total
    FROM laps
    JOIN bibs ON laps.uid = bibs.uid
    JOIN users ON bibs.bib = users.bib
    $where
";
$total = mysqli_fetch_assoc(mysqli_query($link, $countQuery))['total'];
$totalPages = ceil($total / $perPage);

$query = "
    SELECT laps.id as id, laps.time as time, laps.control, laps.is_canceled,
           users.firstname, users.lastname, users.bib as bib,
           races.label AS race_label
    FROM laps
    JOIN bibs ON laps.uid = bibs.uid
    JOIN users ON bibs.bib = users.bib
    LEFT JOIN races ON users.race = races.id
    $where
    ORDER BY $order $dir
    LIMIT $offset, $perPage
";

$results = mysqli_query($link, $query);

function sortLink($col, $order, $dir, $bib, $page) {
    $newDir = ($order === $col && $dir === 'ASC') ? 'DESC' : 'ASC';
    $params = ['order' => $col, 'dir' => $newDir, 'page' => $page];
    if ($bib) $params['bib'] = $bib;
    return 'ubbc-laps.php?' . http_build_query($params);
}
?>

<?php include('ubbc-header.html'); ?>
<section class="container-fluid px-2">
<h1 class="fl-txt-gray fl-txt-25 text-uppercase pt-2 text-center">LAPS</h1>

<form class="mb-3 text-center" method="get" action="ubbc-laps.php">
  <label class="me-2">Bib :
    <input type="number" name="bib" value="<?= htmlspecialchars($_GET['bib'] ?? '') ?>" class="form-control d-inline-block w-auto" />
  </label>
  <button type="submit" class="btn btn-sm fl-bg-prune fl-txt-white fl-bg-hov-sadsea">Filter</button>
</form>

<div class="table-responsive">
  <table class="table table-bordered table-striped text-center align-middle w-100">
    <thead class="align-middle">
      <tr>
        <th class="p-1"><a href="<?= sortLink('id', $order, $dir, $bibFilter, $page) ?>" class="text-decoration-none  fl-txt-electric fl-txt-hov-sadsea">#</a></th>
        <th class="p-1"><a href="<?= sortLink('bib', $order, $dir, $bibFilter, $page) ?>" class="text-decoration-none  fl-txt-electric fl-txt-hov-sadsea">Bib</a></th>
        <th class="p-1">Lastname</th>
        <th class="p-1">Firstname</th>
        <th class="p-1"><a href="<?= sortLink('time', $order, $dir, $bibFilter, $page) ?>" class="text-decoration-none  fl-txt-electric fl-txt-hov-sadsea">Time</a></th>
        <th class="p-1">Control</th>
        <th class="p-1">Race</th>
        <th class="p-1">Canceled</th>
      </tr>
    </thead>
    <tbody>
      <?php if (mysqli_num_rows($results) === 0): ?>
        <tr><td colspan="8">Aucun tour enregistré.</td></tr>
      <?php endif; ?>
      <?php while ($record = mysqli_fetch_assoc($results)) : ?>
      <tr>
        <td class="p-1"><?= $record['id'] ?></td>
        <td class="p-1"><?= $record['bib'] ?></td>
        <td class="p-1"><?= strtoupper($record['lastname']) ?></td>
        <td class="p-1"><?= ucfirst($record['firstname']) ?></td>
        <td class="p-1">
          <form method="post" action="ubbc-laps.php" class="d-flex justify-content-center align-items-center gap-1 flex-nowrap">
            <input type="hidden" name="lap_id" value="<?= $record['id'] ?>">
            <input type="hidden" name="action" value="edit_time">
            <input type="datetime-local" name="new_time" value="<?= str_replace(' ', 'T', $record['time']) ?>" class="form-control form-control-sm w-auto">
            <button type="submit" class="btn btn-sm fl-bg-prune fl-txt-white fl-bg-hov-electric">✓</button>
          </form>
        </td>
        <td class="p-1"><?= $record['control'] ?></td>
        <td class="p-1"><?= $record['race_label'] ?? '-' ?></td>
        <td class="p-1">
          <form method="post" action="ubbc-laps.php" style="margin:0;">
            <input type="hidden" name="lap_id" value="<?= $record['id'] ?>">
            <input type="hidden" name="action" value="toggle_cancel">
            <button type="submit"
              class="btn btn-sm <?= $record['is_canceled']
                  ? 'fl-bg-anis fl-bd-anis fl-txt-prune'
                  : 'fl-bg-peach fl-bd-peach fl-txt-white' ?>">
              <?= $record['is_canceled'] ? 'Oui' : 'Non' ?>
            </button>
          </form>
        </td>
      </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
</div>

<div class="row justify-content-center my-3">
  <div class="btn-group" role="group" aria-label="Pagination">
    <?php
      $firstLink = 'ubbc-laps.php?' . http_build_query(['page' => 1, 'order' => $order, 'dir' => $dir] + ($bibFilter ? ['bib' => $bibFilter] : []));
      $lastLink = 'ubbc-laps.php?' . http_build_query(['page' => $totalPages, 'order' => $order, 'dir' => $dir] + ($bibFilter ? ['bib' => $bibFilter] : []));
    ?>
    <a href="<?= $firstLink ?>" class="btn btn-sm fl-bg-white fl-txt-electric">&laquo;</a>
    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
      <?php
        $params = ['page' => $i, 'order' => $order, 'dir' => $dir];
        if ($bibFilter) $params['bib'] = $bibFilter;
        $url = 'ubbc-laps.php?' . http_build_query($params);
        $active = ($i == $page) ? ' fl-bg-electric fl-txt-white' : ' fl-bg-white fl-txt-electric';
      ?>
      <a href="<?= $url ?>" class="btn btn-sm<?= $active ?>"><?= $i ?></a>
    <?php endfor; ?>
  <a href="<?= $lastLink ?>" class="btn btn-sm fl-bg-white fl-txt-electric">&raquo;</a>
  </div>
</div>

<div class="row justify-content-center mt-4">
  <a class="mx-1 mb-2 btn btn-lg fl-bg-electric fl-txt-white fl-bg-hov-peach" href="ubbc-grid.php"><i class="fal fa-recycle mx-1"></i>grid</a>
  <a class="mx-1 mb-2 btn btn-lg fl-bg-blood fl-txt-white fl-bg-hov-electric" href="ubbc-grid-finish.php"><i class="fal fa-flag-checkered mx-1"></i>grid-finish</a>
  <a class="mx-1 mb-2 btn btn-lg fl-bg-sadsea fl-txt-white fl-bg-hov-peach" href="ubbc-live.php"><i class="fal fa-person-running mx-1"></i>live</a>
</div>
</section>
<?php include('ubbc-footer.html'); ?>
