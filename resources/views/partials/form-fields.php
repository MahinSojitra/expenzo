<?php foreach ($fields as $name => $field):
    $type = $field['type'] ?? 'text';
    $value = $type === 'password' ? '' : old($name, $record[$name] ?? $field['default'] ?? '');
    $error = $errors[$name] ?? null;
?>
<div class="<?=!empty($field['wide']) ? 'col-12' : 'col-md-6'?> mb-3">
    <label for="<?=e($name)?>" class="form-label"><?=e($field['label'])?><?php if (!empty($field['required'])): ?> <span class="text-danger" aria-hidden="true">*</span><?php endif; ?></label>
    <?php if ($type === 'icon'): ?>
    <select id="<?=e($name)?>" name="<?=e($name)?>" data-icon-picker class="form-select <?=$error ? 'is-invalid' : ''?>" <?=!empty($field['required']) ? 'required' : ''?> <?=$error ? 'aria-invalid="true" aria-describedby="'.e($name).'-error"' : ''?>>
        <option value="<?=e($value)?>" selected><?=e($value)?></option>
    </select>
    <?php elseif ($type === 'select'): ?>
    <select id="<?=e($name)?>" name="<?=e($name)?>" class="form-select <?=$error ? 'is-invalid' : ''?>" <?=!empty($field['required']) ? 'required' : ''?> <?=$error ? 'aria-invalid="true" aria-describedby="'.e($name).'-error"' : ''?>>
        <?php foreach ($field['options'] as $optionValue => $option):
            $label = is_array($option) ? ($option['label'] ?? $optionValue) : $option;
            $icon = is_array($option) ? ($option['icon'] ?? null) : null;
        ?><option value="<?=e($optionValue)?>" <?=$icon ? 'data-icon="'.e($icon).'"' : ''?> <?=selected($value, $optionValue)?>><?=e($label)?></option><?php endforeach; ?>
    </select>
    <?php elseif ($type === 'textarea'): ?>
    <textarea id="<?=e($name)?>" name="<?=e($name)?>" class="form-control <?=$error ? 'is-invalid' : ''?>" rows="4" maxlength="16000" <?=$error ? 'aria-invalid="true" aria-describedby="'.e($name).'-error"' : ''?>><?=e($value)?></textarea>
    <?php else: ?>
    <input id="<?=e($name)?>" name="<?=e($name)?>" type="<?=e($type)?>" value="<?=e($value)?>" class="form-control <?=$error ? 'is-invalid' : ''?>" <?=!empty($field['required']) ? 'required' : ''?> <?=$type === 'password' ? 'data-password-toggle' : ''?>
        <?php foreach (['min', 'max', 'step', 'maxlength', 'minlength', 'autocomplete'] as $attr): if (isset($field[$attr])): ?> <?=e($attr)?>="<?=e($field[$attr])?>"<?php endif; endforeach; ?>
        <?=$error ? 'aria-invalid="true" aria-describedby="'.e($name).'-error"' : ''?>>
    <?php endif; ?>
    <?php if ($error): ?><div id="<?=e($name)?>-error" class="invalid-feedback d-block"><?=e($error)?></div><?php endif; ?>
    <?php if (!empty($field['help'])): ?><div class="form-text"><?=e($field['help'])?></div><?php endif; ?>
</div>
<?php endforeach; ?>
