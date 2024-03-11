<h2>Informations</h2>

<form id="informationForm" method="POST" action="<?php echo $formAction; ?>" enctype="multipart/form-data" class="mb-3">
    <div class="row">
        <div class="col">
            <h3 class="mb-3">Method</h3>

            <div class="mb-3">
                <select id="method-select" class="form-select" name="method">
                    <option value="0" <?php echo $values['method'] === '0' ? 'selected' : ''; ?>>POST / PUT</option>
                    <option value="1" <?php echo $values['method'] === '1' ? 'selected' : ''; ?>> GET / DELETE</option>
                </select>
            </div>

            <h3 class="mb-3">Headers</h3>

            <div class="mb-3">
                <label for="request-target" class="form-label">(request-target)</label>
                <input type="text" class="form-control" name="request-target" id="request-target" value="<?php echo $values['request-target']; ?>">
            </div>

            <div class="mb-3">
                <label for="date" class="form-label">date</label>
                <input type="text" class="form-control" name="date" id="date" value="<?php echo $values['date']; ?>">
            </div>

            <div class="mb-3">
                <label for="x-request-id" class="form-label">x-request-id</label>
                <input type="text" class="form-control" name="x-request-id" id="x-request-id" value="<?php echo $values['x-request-id']; ?>">
            </div>
        </div>
        <div class="col">
            <div id="payload">
                <h3 class="mb-3">Body</h3>

                <div class="mb-3">
                    <label for="payload" class="form-label">Payload</label>
                    <textarea class="form-control" name="payload" id="payload" rows="12"><?php echo $values['payload']; ?></textarea>
                </div>
            </div>
        </div>
    </div>

    <h3 class="mb-3">Application</h3>

    <div class="mb-3">
        <label for="app-id" class="form-label">App ID</label>
        <input type="text" class="form-control" name="app-id" id="app-id" value="<?php echo $values['app-id']; ?>">
    </div>

    <div class="mb-3">
        <label for="private-key" class="form-label">Private Key</label>
        <?php
        if ($isFormSubmitted) {
            ?>
            <textarea  class="form-control" rows="6" name="private-key" id="private-key"><?php echo isset($privateKeyStr) ? $privateKeyStr : ''; ?></textarea>
        <?php
        } else {
            ?>
            <input type="hidden" name="MAX_FILE_SIZE" value="10000"> <!-- 10 Ko max -->
            <input class="form-control" type="file" name="private-key" id="private-key">
        <?php
        }
?>
    </div>
    <button type="submit" name="submit" class="btn btn-primary">Generate signature</button>
</form>