<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'db.php';

function normalize_name($s) {
    $s = mb_strtolower(trim($s), 'UTF-8');
    $s = preg_replace('/[^a-z\s]/u', '', $s);
    $s = preg_replace('/\s+/', ' ', $s);
    return $s;
}

function letter_value($ch) {
    $map = [
         "a"=>1, "b"=>2, "c"=>3, "d"=>4, "e"=>5, "f"=>8, "g"=>3, "h"=>5, "i"=>1,
  "j"=>1, "k"=>2, "l"=>3, "m"=>4, "n"=>5, "o"=>7, "p"=>8, "q"=>1, "r"=>2,
  "s"=>3, "t"=>4, "u"=>6, "v"=>6, "w"=>6, "x"=>5, "y"=>1, "z"=>7
    ];

    return $map[$ch] ?? 0;
}

function reduce_number($num) {
    if ($num == 11 || $num == 22) return (string)$num;
    while ($num > 9) {
        $sum = 0;
        foreach (str_split((string)$num) as $d) $sum += intval($d);
        if ($sum == 11 || $sum == 22) return (string)$sum;
        $num = $sum;
    }
    return (string)$num;
}

$action = $_GET['action'] ?? ($_POST['action'] ?? '');

if ($action === 'add') {
    $name = $_POST['name'] ?? '';
    if (trim($name) === '') { echo json_encode(['status'=>'error','message'=>'Name is required']); exit; }
    $normalized = normalize_name($name);
    if ($normalized === '') { echo json_encode(['status'=>'error','message'=>'Name must contain alphabet characters']); exit; }

    $sum = 0;
    for ($i=0; $i<mb_strlen($normalized,'UTF-8'); $i++) {
        $ch = mb_substr($normalized, $i, 1, 'UTF-8');
        if ($ch === ' ') continue;
        $sum += letter_value($ch);
    }
    $reduced = reduce_number($sum);

    // duplicate check (non-deleted)
    $stmt = $pdo->prepare("SELECT * FROM names WHERE normalized_name = :normal AND is_deleted = 0 LIMIT 1");
    $stmt->execute([':normal' => $normalized]);
    $existing = $stmt->fetch();
    if ($existing) { echo json_encode(['status'=>'exists','message'=>'Name already exists','record'=>$existing]); exit; }

    $ins = $pdo->prepare("INSERT INTO names (original_name, normalized_name, computed_number, is_deleted) VALUES (:orig,:normal,:num,0)");
    $ins->execute([':orig'=>$name, ':normal'=>$normalized, ':num'=>$reduced]);
    $id = $pdo->lastInsertId();

    $stmt = $pdo->prepare("SELECT * FROM names WHERE id = :id"); $stmt->execute([':id'=>$id]);
    echo json_encode(['status'=>'ok','record'=>$stmt->fetch()]);
    exit;
}

if ($action === 'list') {
    $search = $_GET['search'] ?? '';
    $number = $_GET['number'] ?? '';
    $params = [];
    $where = 'is_deleted = 0';
    if ($search !== '') { $where .= ' AND (original_name LIKE :s OR normalized_name LIKE :s)'; $params[':s'] = "%$search%"; }
    if ($number !== '') { $where .= ' AND computed_number = :num'; $params[':num'] = $number; }

    $sql = "SELECT id, original_name, normalized_name, computed_number, created_at FROM names WHERE $where ORDER BY created_at DESC";
    $stmt = $pdo->prepare($sql); $stmt->execute($params);
    echo json_encode(['status'=>'ok','data'=>$stmt->fetchAll()]);
    exit;
}

if ($action === 'advantages') {
    $num = $_GET['number'] ?? '';
    if ($num === '') { echo json_encode(['status'=>'error','message'=>'number required']); exit; }
    $stmt = $pdo->prepare("SELECT * FROM advantages WHERE number_value = :n"); $stmt->execute([':n'=>$num]);
    echo json_encode(['status'=>'ok','data'=>$stmt->fetchAll()]);
    exit;
}

// Soft delete
if ($action === 'delete') {
    $id = intval($_POST['id'] ?? 0);
    if ($id <= 0) { echo json_encode(['status'=>'error','message'=>'Invalid ID']); exit; }
    $pdo->prepare("UPDATE names SET is_deleted = 1 WHERE id = :id")->execute([':id'=>$id]);
    echo json_encode(['status'=>'ok','message'=>'Record deleted']); exit;
}

// Optional restore
if ($action === 'restore') {
    $id = intval($_POST['id'] ?? 0);
    if ($id <= 0) { echo json_encode(['status'=>'error','message'=>'Invalid ID']); exit; }
    $pdo->prepare("UPDATE names SET is_deleted = 0 WHERE id = :id")->execute([':id'=>$id]);
    echo json_encode(['status'=>'ok','message'=>'Record restored']); exit;
}

echo json_encode(['status'=>'error','message'=>'No valid action']); exit;
