<form action="company_save.php" method="post" enctype="multipart/form-data">
    <input type="hidden" name="com_id" value="<?= $company['com_id'] ?>">

    <label>ชื่อบริษัท</label>
    <input type="text" name="com_name" value="<?= htmlspecialchars($company['com_name'], ENT_QUOTES, 'UTF-8') ?>" required>

    <label>รายละเอียด</label>
    <textarea name="com_detail" rows="5"><?= htmlspecialchars($company['com_detail'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>

    <label>รูปบริษัท</label>
    <input type="file" name="com_img" accept="image/*">

    <button type="submit">บันทึก</button>
</form>
