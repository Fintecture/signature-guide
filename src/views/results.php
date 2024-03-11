<hr>
<div id="results">
    <h2>Results</h2>

    <h3 class="mb-3">Steps in detail</h3>

    <ul class="nav nav-tabs mb-3" id="languages" role="tablist">
        <?php
        foreach ($tabs as $key => $value) {
            echo '<li class="nav-item" role="presentation">';
            echo '<button class="nav-link '.($language === $key ? 'active' : '').'" id="'.$key.'-tab" data-bs-toggle="tab" data-bs-target="#'.$key.'" type="button" role="tab">'.$value.'</button>';
            echo '</li>';
        }
        ?>
    </ul>

    <div class="tab-content">
    <?php
    foreach ($tabs as $key => $value) {
        ?>
        <div class="tab-pane fade <?php echo $language === $key ? 'show active' : ''; ?>" id="<?php echo $key; ?>" role="tabpanel" aria-labelledby="<?php echo $key; ?>-tab">
        <?php
        if (isset($requirements[$key])) {
            ?>
            <div class="alert alert-info">
                <b>Requirements: </b> <?php echo $requirements[$key]; ?>
            </div>
        <?php
        } ?>
            <div class="accordion">
                <?php
                foreach ($steps[$key] as $index => $step) {
                    ?>
                    <div class="accordion-item">
                        <h3 class="accordion-header" id="panelsStayOpen-heading<?php echo $index; ?>">
                            <button class="accordion-button <?php echo (int) $index !== 0 ? 'collapsed' : ''; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#panelsStayOpen-collapse<?php echo $index; ?>">
                                Step <?php echo (int) $index + 1; ?> - <?php echo $step['title']; ?>
                            </button>
                        </h3>
                        <div id="panelsStayOpen-collapse<?php echo $index; ?>" class="accordion-collapse collapse <?php echo (int) $index === 0 ? 'show' : ''; ?>">
                            <div class="accordion-body">
                                <?php
                                if (isset($step['help'])) {
                                    echo '<p><i class="bi bi-info-circle"></i> ' . $step['help'] . '</p>';
                                } ?>
                                <pre class="mb-3"><code class="language-<?php echo $key; ?>"><?php echo htmlentities($step['code']); ?></code></pre>

                                <?php
                                if (isset($step['key']) && isset($step['value'])) {
                                    echo '<h4>' . $step['key'] . '</h4>';
                                    echo '<div class="card"><div class="card-body">';
                                    if (isset($step['value_type']) && $step['value_type'] === 'json') {
                                        echo '<pre><code class="language-json">' . $step['value'] . '</code></pre>';
                                    } else {
                                        echo $step['value'];
                                    }
                                    echo '</div></div>';
                                } ?>
                            </div>
                        </div>
                    </div>
                <?php
                } ?>
            </div>
            <div class="alert alert-info">
                <p><?php echo $completeExamples[$key] ?></p>
            </div>
        </div>
    <?php
    }
        ?>
    </div>
</div>