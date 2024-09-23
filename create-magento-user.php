<?php
    define( 'USERNAME', 'skidwell' );
    define( 'PASSWORD', 'webmyle55' );
    define( 'FIRSTNAME', 'Stephen' );
    define( 'LASTNAME', 'Kidwell' );
    define( 'EMAIL', 'sk@mailinator.com' );
    include_once( 'app/Mage.php' );

    Mage::app( 'admin' );
    try {

        $adminUserModel = Mage::getModel( 'admin/user' );
        $adminUserModel->setUsername( USERNAME )
            ->setFirstname( FIRSTNAME )
            ->setLastname( LASTNAME )
            ->setEmail( EMAIL )
            ->setNewPassword( PASSWORD )
            ->setPasswordConfirmation( PASSWORD )
            ->setIsActive( true )
            ->save();
        $adminUserModel->setRoleIds( array( 1 ) )

            ->setRoleUserId( $adminUserModel->getUserId() )
            ->saveRelations();
        echo 'User created.';

    } catch( Exception $e ) {
        echo $e->getMessage();
    }