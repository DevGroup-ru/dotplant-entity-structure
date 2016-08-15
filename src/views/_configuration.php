<?php
/**
 * @var $this \yii\web\View
 */

echo '<div class="box-body">';
echo $form->field($model, 'defaultPageSize');
echo $form->field($model, 'showHiddenInTree')->checkbox();
echo '</div>';
