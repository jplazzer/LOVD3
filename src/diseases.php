<?php
/*******************************************************************************
 *
 * LEIDEN OPEN VARIATION DATABASE (LOVD)
 *
 * Created     : 2010-07-27
 * Modified    : 2012-04-25
 * For LOVD    : 3.0-beta-04
 *
 * Copyright   : 2004-2012 Leiden University Medical Center; http://www.LUMC.nl/
 * Programmers : Ing. Ivo F.A.C. Fokkema <I.F.A.C.Fokkema@LUMC.nl>
 *               Ing. Ivar C. Lugtenburg <I.C.Lugtenburg@LUMC.nl>
 *
 *
 * This file is part of LOVD.
 *
 * LOVD is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * LOVD is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with LOVD.  If not, see <http://www.gnu.org/licenses/>.
 *
 *************/

define('ROOT_PATH', './');
require ROOT_PATH . 'inc-init.php';

if ($_AUTH) {
    // If authorized, check for updates.
    require ROOT_PATH . 'inc-upgrade.php';
}





if (PATH_COUNT == 1 && !ACTION) {
    // URL: /diseases
    // View all entries.

    define('PAGE_TITLE', 'View diseases');
    $_T->printHeader();
    $_T->printTitle();

    require ROOT_PATH . 'class/object_diseases.php';
    $_DATA = new LOVD_Disease();
    $_DATA->viewList('Diseases', 'individualid');

    $_T->printFooter();
    exit;
}





if (PATH_COUNT == 2 && ctype_digit($_PE[1]) && !ACTION) {
    // URL: /diseases/00001
    // View specific entry.

    $nID = sprintf('%05d', $_PE[1]);
    define('PAGE_TITLE', 'View disease #' . $nID);
    $_T->printHeader();
    $_T->printTitle();

    // Load appropiate user level for this disease.
    lovd_isAuthorized('disease', $nID); // This call will make database queries if necessary.

    require ROOT_PATH . 'class/object_diseases.php';
    $_DATA = new LOVD_Disease();
    $zData = $_DATA->viewEntry($nID);

    $sNavigation = '';
    if ($_AUTH && $_AUTH['level'] >= LEVEL_CURATOR) {
        $sNavigation .= '<A href="columns/Phenotype/' . $nID . '?order">Re-order all ' . $zData['symbol'] . ' phenotype columns';
        if ($_AUTH['level'] >= LEVEL_MANAGER) {
            // Authorized user (admin or manager) is logged in. Provide tools.
            $sNavigation .= ' | <A href="' . CURRENT_PATH . '?edit">Edit disease information</A>';
            $sNavigation .= ' | <A href="' . CURRENT_PATH . '?delete">Delete disease entry</A>';
        }
    }

    if ($sNavigation) {
        print('      <IMG src="gfx/trans.png" alt="" width="1" height="5"><BR>' . "\n");
        lovd_showNavigation($sNavigation);
    }
    
    $_GET['search_diseaseids'] = $nID;
    print('<BR><BR>' . "\n\n");
    $_T->printTitle('Individuals', 'H4');
    require ROOT_PATH . 'class/object_individuals.php';
    $_DATA = new LOVD_Individual();
    $_DATA->setSortDefault('id');
    $_DATA->viewList('Individuals_for_D_VE', array('panelid', 'diseaseids'), true, true);
    
    $_T->printFooter();
    exit;
}





if (PATH_COUNT == 2 && !ctype_digit($_PE[1]) && !ACTION) {
    // URL: /diseases/DMD
    // Try to find a disease by its abbreviation and forward.
    // When we have multiple hits, refer to listView.

    $sID = rawurldecode($_PE[1]);
    $aDiseases = $_DB->query('SELECT id FROM ' . TABLE_DISEASES . ' WHERE symbol = ?', array($sID))->fetchAllColumn();
    $n = count($aDiseases);
    if (!$n) {
        define('PAGE_TITLE', 'View disease');
        $_T->printHeader();
        $_T->printTitle();
        lovd_showInfoTable('No such ID!', 'stop');
        $_T->printFooter();
    } elseif ($n == 1) {
        header('Location: ' . lovd_getInstallURL() . $_PE[0] . '/' . $aDiseases[0]);
    } else {
        // Multiple hits. Forward to exact match search.
        header('Location: ' . lovd_getInstallURL() . $_PE[0] . '?search_symbol=%3D%22' . rawurlencode($sID) . '%22');
    }
    exit;
}





if (PATH_COUNT == 1 && ACTION == 'create') {
    // URL: /diseases?create
    // Create a new entry.

    define('PAGE_TITLE', 'Create a new disease information entry');
    define('LOG_EVENT', 'DiseaseCreate');

    // Require manager clearance.
    // FIXME; allow curator to create disease entries linked to own genes?
    lovd_requireAUTH(LEVEL_MANAGER);

    require ROOT_PATH . 'class/object_diseases.php';
    $_DATA = new LOVD_Disease();
    require ROOT_PATH . 'inc-lib-form.php';

    if (!empty($_POST)) {
        lovd_errorClean();

        $_DATA->checkFields($_POST);

        if (!lovd_error()) {
            // Fields to be used.
            $aFields = array('symbol', 'name', 'id_omim', 'created_by', 'created_date');

            // Prepare values.
            $_POST['created_by'] = $_AUTH['id'];
            $_POST['created_date'] = date('Y-m-d H:i:s');

            $nID = $_DATA->insertEntry($_POST, $aFields);

            // FIXME; add this and next block to a function, just like lovd_addAllDefaultCustomColumnsForGene().
            $aAdded = $_DB->query('DESCRIBE ' . TABLE_PHENOTYPES)->fetchAllColumn();
            
            $qStandardCustomCols = $_DB->query('SELECT * FROM ' . TABLE_COLS . ' WHERE id LIKE "Phenotype/%" AND (standard = 1 OR hgvs = 1)');
            while ($zStandard = $qStandardCustomCols->fetchAssoc()) {
                if (!in_array($zStandard['id'], $aAdded)) {
                    $_DB->query('ALTER TABLE ' . TABLE_PHENOTYPES . ' ADD COLUMN `' . $zStandard['id'] . '` ' . $zStandard['mysql_type']);
                    $_DB->query('INSERT INTO ' . TABLE_ACTIVE_COLS . ' VALUES(?, ?, NOW())', array($zStandard['id'], $_AUTH['id']));
                }
                $_DB->query('INSERT INTO ' . TABLE_SHARED_COLS . ' VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NULL, NULL)', array($nID, $zStandard['id'], $zStandard['col_order'], $zStandard['width'], $zStandard['mandatory'], $zStandard['description_form'], $zStandard['description_legend_short'], $zStandard['description_legend_full'], $zStandard['select_options'], $zStandard['public_view'], $zStandard['public_add'], $_AUTH['id']));
            }

            // Write to log...
            lovd_writeLog('Event', LOG_EVENT, 'Created disease information entry ' . $nID . ' - ' . $_POST['symbol'] . ' (' . $_POST['name'] . ')');

            // Add genes.
            $aSuccess = array();
            if (!empty($_POST['genes'])) {
                foreach ($_POST['genes'] as $sGene) {
                    // Add gene to disease.
                    // FIXME; Nu dat PDO beschikbaar is, doe dit in een prepared statement met multiple executes.
                    $q = $_DB->query('INSERT INTO ' . TABLE_GEN2DIS . ' VALUES (?, ?)', array($sGene, $nID), false);
                    if (!$q) {
                        // Silent error.
                        lovd_writeLog('Error', LOG_EVENT, 'Disease information entry ' . $nID . ' - ' . $_POST['symbol'] . ' - could not be added to gene ' . $sGene);
                    } else {
                        $aSuccess[] = $sGene;
                    }
                }
            }
            if (count($aSuccess)) {
                lovd_writeLog('Event', LOG_EVENT, 'Disease information entry ' . $nID . ' - ' . $_POST['symbol'] . ' - successfully added to gene(s) ' . implode(', ', $aSuccess));
            }

            // Thank the user...
            header('Refresh: 3; url=' . lovd_getInstallURL() . CURRENT_PATH . '/' . $nID);

            $_T->printHeader();
            $_T->printTitle();
            lovd_showInfoTable('Successfully created the disease information entry!', 'success');

            $_T->printFooter();
            exit;

        } else {
            // Because we're sending the data back to the form, I need to unset the password fields!
            unset($_POST['password']); // Currently does not have an effect here.
        }

    } else {
        // Default values.
        $_DATA->setDefaultValues();
    }



    $_T->printHeader();
    $_T->printTitle();

    if (GET) {
        print('      To create a new disease information entry, please fill out the form below.<BR>' . "\n" .
              '      <BR>' . "\n\n");
    }

    lovd_errorPrint();

    // Tooltip JS code.
    lovd_includeJS('inc-js-tooltip.php');

    // Table.
    print('      <FORM action="' . CURRENT_PATH . '?' . ACTION . '" method="post">' . "\n");

    // Array which will make up the form table.
    $aForm = array_merge(
                 $_DATA->getForm(),
                 array(
                        array('', '', 'submit', 'Create disease information entry'),
                      ));
    lovd_viewForm($aForm);

    print('</FORM>' . "\n\n");

    $_T->printFooter();
    exit;
}





if (PATH_COUNT == 2 && ctype_digit($_PE[1]) && ACTION == 'edit') {
    // URL: /diseases/00001?edit
    // Edit a specific entry.

    $nID = sprintf('%05d', $_PE[1]);
    define('PAGE_TITLE', 'Edit disease information entry #' . $nID);
    define('LOG_EVENT', 'DiseaseEdit');

    // Load appropiate user level for this disease.
    lovd_isAuthorized('disease', $nID); // This call will make database queries if necessary.
    lovd_requireAUTH(LEVEL_CURATOR);

    require ROOT_PATH . 'class/object_diseases.php';
    $_DATA = new LOVD_Disease();
    $zData = $_DATA->loadEntry($nID);
    require ROOT_PATH . 'inc-lib-form.php';

    if (!empty($_POST)) {
        lovd_errorClean();

        $_DATA->checkFields($_POST);

        if (!lovd_error()) {
            // Fields to be used.
            $aFields = array('symbol', 'name', 'id_omim', 'edited_by', 'edited_date');

            // Prepare values.
            $_POST['edited_by'] = $_AUTH['id'];
            $_POST['edited_date'] = date('Y-m-d H:i:s');

            $_DATA->updateEntry($nID, $_POST, $aFields);

            // Write to log...
            lovd_writeLog('Event', LOG_EVENT, 'Edited disease information entry ' . $nID . ' - ' . $_POST['symbol'] . ' (' . $_POST['name'] . ')');

            // Change linked genes?
            // Remove genes.
            $aToRemove = array();
            foreach ($zData['genes'] as $sGene) {
                if ($sGene && !in_array($sGene, $_POST['genes'])) {
                    // User has requested removal...
                    $aToRemove[] = $sGene;
                }
            }
            if ($aToRemove) {
                $q = $_DB->query('DELETE FROM ' . TABLE_GEN2DIS . ' WHERE diseaseid = ? AND geneid IN (?' . str_repeat(', ?', count($aToRemove) - 1) . ')', array_merge(array($nID), $aToRemove), false);
                if (!$q) {
                    // Silent error.
                    // FIXME; deze log entries zijn precies andersom dan bij create (wat wordt aan wat toegevoegd/verwijderd). Dat moeten we standaardiseren, maar wellicht even overleggen over LOVD-breed.
                    lovd_writeLog('Error', LOG_EVENT, 'Gene information entr' . (count($aToRemove) == 1? 'y' : 'ies') . ' ' . implode(', ', $aToRemove) . ' could not be removed from disease ' . $nID);
                } else {
                    lovd_writeLog('Event', LOG_EVENT, 'Gene information entr' . (count($aToRemove) == 1? 'y' : 'ies') . ' ' . implode(', ', $aToRemove) . ' successfully removed from disease ' . $nID);
                }
            }

            // Add genes.
            $aSuccess = array();
            $aFailed = array();
            $q = $_DB->prepare('INSERT IGNORE INTO ' . TABLE_GEN2DIS . ' VALUES (?, ?)');
            foreach ($_POST['genes'] as $sGene) {
                if (!in_array($sGene, $zData['genes']) && $sGene != 'None') {
                    // Add disease to gene.
                    $b = $q->execute(array($sGene, $nID), false);
                    if (!$q) {
                        $aFailed[] = $sGene;
                    } else {
                        $aSuccess[] = $sGene;
                    }
                }
            }
            if ($aFailed) {
                // Silent error.
                lovd_writeLog('Error', LOG_EVENT, 'Gene information entr' . (count($aFailed) == 1? 'y' : 'ies') . ' ' . implode(', ', $aFailed) . ' could not be added to disease ' . $nID);
            }
            if ($aSuccess) {
                lovd_writeLog('Event', LOG_EVENT, 'Gene information entr' . (count($aSuccess) == 1? 'y' : 'ies') . ' ' . implode(', ', $aSuccess) . ' successfully added to disease ' . $nID);
            }

            // Thank the user...
            header('Refresh: 3; url=' . lovd_getInstallURL() . CURRENT_PATH);

            $_T->printHeader();
            $_T->printTitle();
            lovd_showInfoTable('Successfully edited the disease information entry!', 'success');

            $_T->printFooter();
            exit;

        } else {
            // Because we're sending the data back to the form, I need to unset the password fields!
            unset($_POST['password']); // Currently does not have an effect here.
        }

    } else {
        // Load current values.
        $_POST = array_merge($_POST, $zData);
    }



    $_T->printHeader();
    $_T->printTitle();

    lovd_errorPrint();

    // Tooltip JS code.
    lovd_includeJS('inc-js-tooltip.php');

    // Table.
    print('      <FORM action="' . CURRENT_PATH . '?' . ACTION . '" method="post">' . "\n");

    // Array which will make up the form table.
    $aForm = array_merge(
                 $_DATA->getForm(),
                 array(
                        array('', '', 'submit', 'Edit disease information entry'),
                      ));
    lovd_viewForm($aForm);

    print('</FORM>' . "\n\n");

    $_T->printFooter();
    exit;
}





if (PATH_COUNT == 2 && ctype_digit($_PE[1]) && ACTION == 'delete') {
    // URL: /diseases/00001?delete
    // Delete specific entry.

    $nID = sprintf('%05d', $_PE[1]);
    define('PAGE_TITLE', 'Delete disease information entry #' . $nID);
    define('LOG_EVENT', 'DiseaseDelete');

    // Require manager clearance.
    // FIXME; allow curators to delete diseases that point to no other genes besides their own?
    lovd_requireAUTH(LEVEL_MANAGER);

    require ROOT_PATH . 'class/object_diseases.php';
    $_DATA = new LOVD_Disease();
    $zData = $_DATA->loadEntry($nID);
    require ROOT_PATH . 'inc-lib-form.php';

    if (!empty($_POST)) {
        lovd_errorClean();

        // Mandatory fields.
        if (empty($_POST['password'])) {
            lovd_errorAdd('password', 'Please fill in the \'Enter your password for authorization\' field.');
        }

        // User had to enter his/her password for authorization.
        if ($_POST['password'] && !lovd_verifyPassword($_POST['password'], $_AUTH['password'])) {
            lovd_errorAdd('password', 'Please enter your correct password for authorization.');
        }

        if (!lovd_error()) {
            // Query text.
            // This also deletes the entries in gen2dis.
            $_DATA->deleteEntry($nID);

            // Write to log...
            lovd_writeLog('Event', LOG_EVENT, 'Deleted disease information entry ' . $nID . ' - ' . $zData['symbol'] . ' (' . $zData['name'] . ')');

            // Thank the user...
            header('Refresh: 3; url=' . lovd_getInstallURL() . 'diseases');

            $_T->printHeader();
            $_T->printTitle();
            lovd_showInfoTable('Successfully deleted the disease information entry!', 'success');

            $_T->printFooter();
            exit;

        } else {
            // Because we're sending the data back to the form, I need to unset the password fields!
            unset($_POST['password']);
        }
    }



    $_T->printHeader();
    $_T->printTitle();

    lovd_errorPrint();

    // Table.
    print('      <FORM action="' . CURRENT_PATH . '?' . ACTION . '" method="post">' . "\n");

    // Array which will make up the form table.
    $aForm = array(
                    array('POST', '', '', '', '50%', '14', '50%'),
                    array('Deleting disease information entry', '', 'print', $zData['symbol'] . ' (' . $zData['name'] . ')'),
                    'skip',
                    array('Enter your password for authorization', '', 'password', 'password', 20),
                    array('', '', 'submit', 'Delete disease information entry'),
                  );
    lovd_viewForm($aForm);

    print('</FORM>' . "\n\n");

    $_T->printFooter();
    exit;
}
?>
