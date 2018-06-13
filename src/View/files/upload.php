<?php include(__DIR__ . '/../common/head.php'); ?>
<h3>Upload form</h3>
<p class="error"><?php echo $error; ?></p>
<form method="post" enctype="multipart/form-data">
    <div>
        <input type="file" name="files[]" />
    </div>
    <input type="button" value="Add more" />
    <br />
    <label>
        <input type="checkbox" name="public" value="1" checked />
        Should be accesible for public
    </label>
    <input type="hidden" name="csrfToken" value="<?php echo $csrfToken; ?>" /><br />
    <input type="submit" />
</form>
<script src="/assets/js/core.js<?php echo $assetsVersioning; ?>"></script>
<?php include(__DIR__ . '/../common/foot.php'); ?>
