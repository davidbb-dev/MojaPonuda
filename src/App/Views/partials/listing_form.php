<form novalidate action="<?= $form['action'] ?>" method="POST" enctype="multipart/form-data" data-mode="<?= $form['mode'] ?>">
    <?= \App\Support\Csrf::field() ?>
    <div class="form_group">
        <label for="listing_name">Unesite naziv oglasa: </label>
        <input type="text" name="listing_name" id="listing_name" value="<?= e($form['values']['data']['name']) ?>" data-error="* Molimo unesite naziv predmeta" required>
        <span class="error_message"></span>
    </div>

    <div class="form_group">
        <label for="listing_description">
            Unesite tekst oglasa:    
        </label>
        <textarea 
            name="listing_description" 
            id="listing_description" 
            data-error="* Molimo unesite opis predmeta"
            required><?= e($form['values']['data']['description']) ?></textarea>
            
        <span class="error_message"></span>
    </div>

    <div class="form_group">
            <fieldset data-error="* Izaberite tip oglasa" >
                <legend>Tip prodaje:</legend>
            
                <label>
                    <input 
                        type="radio"
                        name="listing_type"
                        value="fixed_price" 
                        <?= $form['values']['data']['listing_type'] === 'fixed_price' ? 'checked' : '' ?>
                        <?= $form['fields']['listing_type']['disabled'] ? 'disabled' : '' ?> 
                        required
                    >
                    Kupovina odmah
                </label>

                <label>
                    <input 
                        type="radio"
                        name="listing_type"
                        value="auction" 
                        <?= $form['values']['data']['listing_type'] === 'auction' ? 'checked' : '' ?>
                        <?= $form['fields']['listing_type']['disabled'] ? 'disabled' : '' ?> 
                    >
                    Aukcija
                </label>

            </fieldset>

        <?php if($form['fields']['listing_type']['disabled']): ?>
            <input
                type="hidden"
                name="listing_type"
                value="<?= e($form['values']['data']['listing_type']) ?>"
            >
        <?php endif; ?>

        <span class="error_message"></span>
    </div>
    
    <div class="form_group" id="auction_date_time" style="display:none;">
        <label for="duration">Trajanje aukcije</label>
        <select name="duration" id="duration" required>
            <option value="3">3 dana</option>
            <option value="5">5 dana</option>
            <option value="7">7 dana</option>
            <option value="10">10 dana</option>
        </select>
        <span class="error_message"></span>
    </div>

    <div class="form_group">
        <input 
            type="number" 
            name="listing_starting_price" 
            id="listing_starting_price" 
            value="<?= e($form['values']['data']['starting_price'] ?? '') ?>"
            placeholder="Cena" 
            min = "1" 
            data-error="* Unesite cenu" 
            required
        >
        <span class="error_message"></span>
    </div>

    <div class="form_group">
        <select name="category_id" id="category_id" required>
            <option value="">Izaberite kategoriju</option>

            <?php foreach($allCategories as $category):?>
            <option value="<?= htmlspecialchars($category['category_id'], ENT_QUOTES, 'UTF-8') ?>" 
                <?= (int)$category['category_id'] === (int)($form['values']['data']['category_id'] ?? 0) ? 'selected' : '' ?>> 
                <?= htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8') ?> 
            </option>
            <?php endforeach; ?>
        </select>
        <span class="error_message"></span>
    </div>

    <div class="form_group">

        <div id="custom_file_input">
            <span id="custom_file_input_span">
                Dodajte slike (max 15)
            </span>
            <input
                type="file"
                name="file_input[]"
                id="file_input"
                accept="image/*"
                multiple
                data-role="images-input"
            >
        </div>

        <div id="images_preview"></div>

        <span class="error_message"></span>
    </div>

    <div class="form_group">
        <button 
            type="submit" 
            id="<?= $form['buttons']['primary']['id'] ?>" 
            name="<?= $form['buttons']['primary']['name'] ?>" 
            value="<?= $form['buttons']['primary']['value'] ?>" 
            class="form_button">
            <?= $form['buttons']['primary']['label'] ?>
        </button>

        <button 
            type="submit" 
            id="<?= $form['buttons']['secondary']['id'] ?>" 
            name="<?= $form['buttons']['secondary']['name'] ?>" 
            value="<?= $form['buttons']['secondary']['value'] ?>" 
            class="form_button">
            <?= $form['buttons']['secondary']['label'] ?>
        </button>
    </div>
</form>