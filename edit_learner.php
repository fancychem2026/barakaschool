
<?php
require_once "config.php";
require_once "functions.php";
requireAdmin();

$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    http_response_code(400);
    exit("Invalid learner ID.");
}

$stmt = $pdo->prepare("SELECT * FROM learners WHERE id = ?");
$stmt->execute([$id]);
$learner = $stmt->fetch();

if (!$learner) {
    http_response_code(404);
    exit("Learner not found.");
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    verifyCsrf();

    $fields = [
        "admission_number" => trim($_POST["admission_number"] ?? ""),
        "upi_number" => trim($_POST["upi_number"] ?? ""),
        "nemis_number" => trim($_POST["nemis_number"] ?? ""),
        "first_name" => trim($_POST["first_name"] ?? ""),
        "last_name" => trim($_POST["last_name"] ?? ""),
        "date_of_birth" => $_POST["date_of_birth"] ?? "",
        "gender" => $_POST["gender"] ?? "",
        "grade" => $_POST["grade"] ?? "",
        "guardian_name" => trim($_POST["guardian_name"] ?? ""),
        "guardian_phone" => trim($_POST["guardian_phone"] ?? ""),
        "guardian_email" => trim($_POST["guardian_email"] ?? ""),
        "address" => trim($_POST["address"] ?? "")
    ];

    $genders = ["Male", "Female", "Other"];
    $grades = [
        "PP1", "PP2",
        "Grade 1", "Grade 2", "Grade 3",
        "Grade 4", "Grade 5", "Grade 6",
        "Grade 7", "Grade 8", "Grade 9"
    ];

    $date = DateTime::createFromFormat(
        "!Y-m-d",
        $fields["date_of_birth"]
    );

    $validDate = $date &&
        $date->format("Y-m-d") === $fields["date_of_birth"] &&
        $fields["date_of_birth"] <= date("Y-m-d");

    if (
        $fields["admission_number"] === "" ||
        $fields["first_name"] === "" ||
        $fields["last_name"] === "" ||
        !$validDate ||
        !in_array($fields["gender"], $genders, true) ||
        !in_array($fields["grade"], $grades, true) ||
        $fields["guardian_name"] === "" ||
        $fields["guardian_phone"] === "" ||
        ($fields["guardian_email"] !== "" &&
         !filter_var($fields["guardian_email"], FILTER_VALIDATE_EMAIL))
    ) {
        $error = "Check the required fields and entered details.";
    } else {
        try {
            $update = $pdo->prepare(
                "UPDATE learners SET
                    admission_number = ?,
                    upi_number = ?,
                    nemis_number = ?,
                    first_name = ?,
                    last_name = ?,
                    date_of_birth = ?,
                    gender = ?,
                    grade = ?,
                    guardian_name = ?,
                    guardian_phone = ?,
                    guardian_email = ?,
                    address = ?
                 WHERE id = ?"
            );

            $update->execute([
                $fields["admission_number"],
                $fields["upi_number"] !== "" ? $fields["upi_number"] : null,
                $fields["nemis_number"] !== "" ? $fields["nemis_number"] : null,
                $fields["first_name"],
                $fields["last_name"],
                $fields["date_of_birth"],
                $fields["gender"],
                $fields["grade"],
                $fields["guardian_name"],
                $fields["guardian_phone"],
                $fields["guardian_email"] !== "" ? $fields["guardian_email"] : null,
                $fields["address"] !== "" ? $fields["address"] : null,
                $id
            ]);

            header("Location: learners.php?updated=1");
            exit;
        } catch (PDOException $ex) {
            if ($ex->getCode() === "23000") {
                $error = "An admission, UPI or NEMIS number may already be in use.";
            } else {
                error_log($ex->getMessage());
                $error = "The record could not be updated.";
            }
        }
    }

    $learner = array_merge($learner, $fields);
}

function selectedOption($actual, $expected) {
    return $actual === $expected ? "selected" : "";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edit Learner | Baraka Academy</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<main class="container">
<section class="panel">
    <h1>Edit Learner Details</h1>

    <?php if ($error !== ""): ?>
        <p class="error"><?= e($error) ?></p>
    <?php endif; ?>

    <form method="post">
        <input type="hidden" name="csrf_token"
               value="<?= e(csrfToken()) ?>">

        <?php
        $textFields = [
            "admission_number" => "Admission Number",
            "upi_number" => "UPI Number",
            "nemis_number" => "NEMIS Number",
            "first_name" => "First Name",
            "last_name" => "Last Name",
            "guardian_name" => "Guardian Name",
            "guardian_phone" => "Guardian Phone",
            "guardian_email" => "Guardian Email"
        ];

        foreach ($textFields as $name => $label):
        ?>
            <label for="<?= e($name) ?>"><?= e($label) ?></label>
            <input id="<?= e($name) ?>"
                   name="<?= e($name) ?>"
                   value="<?= e($learner[$name] ?? "") ?>"
                   maxlength="<?= $name === "guardian_email" ? 120 : 120 ?>"
                   <?= in_array($name, [
                       "admission_number", "first_name", "last_name",
                       "guardian_name", "guardian_phone"
                   ], true) ? "required" : "" ?>
                   <?= $name === "guardian_email" ? "type=email" : "" ?>>
        <?php endforeach; ?>

        <label for="date_of_birth">Date of Birth</label>
        <input type="date" id="date_of_birth" name="date_of_birth"
               max="<?= date('Y-m-d') ?>"
               value="<?= e($learner["date_of_birth"]) ?>" required>

        <label for="gender">Gender</label>
        <select id="gender" name="gender" required>
            <?php foreach (["Male", "Female", "Other"] as $g): ?>
                <option value="<?= e($g) ?>"
                    <?= selectedOption($learner["gender"], $g) ?>>
                    <?= e($g) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="grade">Grade</label>
        <select id="grade" name="grade" required>
            <?php foreach ([
                "PP1", "PP2",
                "Grade 1", "Grade 2", "Grade 3",
                "Grade 4", "Grade 5", "Grade 6",
                "Grade 7", "Grade 8", "Grade 9"
            ] as $g): ?>
                <option value="<?= e($g) ?>"
                    <?= selectedOption($learner["grade"], $g) ?>>
                    <?= e($g) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="address">Address</label>
        <textarea id="address" name="address"
                  maxlength="255"><?= e($learner["address"] ?? "") ?></textarea>

        <button class="button" type="submit">Update Learner</button>
        <a class="button" href="learners.php">Cancel</a>
    </form>
</section>
</main>
</body>
</html>