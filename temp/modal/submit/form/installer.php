<div class="fc-form-group fc-form-check">
   
    <?php foreach( fc_installer() as $installer_k => $installer_v ): ?>
    <label class="mb-1">
    <input type="radio" name="installer" value="<?php echo $installer_k; ?>" class="fc-mr-1" required>
    <?php echo $installer_v; ?>

    <?php if( $installer_k == 'no'): ?>
    <span class="fc-label-badge fc-ml-1">Cheaper</span>
    <?php endif; ?>

    </label>    
    <?php endforeach; ?>

</div>