<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * The two column layout.
 *
 * @package   theme_clean
 * @copyright 2013 Moodle, moodle.org
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Get the HTML for the settings bits.
$html = theme_clean_get_html_for_settings($OUTPUT, $PAGE);

$left = (!right_to_left());  // To know if to add 'pull-right' and 'desktop-first-column' classes in the layout for LTR.
echo $OUTPUT->doctype() ?>
<html <?php echo $OUTPUT->htmlattributes(); ?>>
<head>
    <title><?php echo $OUTPUT->page_title(); ?></title>
    <link rel="shortcut icon" href="<?php echo $OUTPUT->favicon(); ?>" />
    <?php echo $OUTPUT->standard_head_html() ?>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css?family=Open+Sans" rel="stylesheet" type="text/css">
</head>

<body <?php echo $OUTPUT->body_attributes('two-column'); ?>>

<?php echo $OUTPUT->standard_top_of_body_html(); ?>

<header role="banner" class="navbar navbar-fixed-top<?php echo $html->navbarclass ?> moodle-has-zindex">
    <nav role="navigation" class="navbar-inner">
        <div class="container-fluid">
            <a class="ufsc" href="http://www.ufsc.br"><?php echo html_writer::img(new moodle_url('/theme/clean/pix/brasao.ufsc.svg'), 'UFSC'); ?></a>
            <a class="second" href="<?php echo $CFG->wwwroot;?>"><h2><?php echo $SITE->shortname; ?></h2></a>
            <a class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse">
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </a>
            <?php echo $OUTPUT->user_menu(); ?>
            <div class="nav-collapse collapse">
                <?php echo $OUTPUT->custom_menu(); ?>
                <ul class="nav pull-right">
                    <li><?php echo $OUTPUT->page_heading_menu(); ?></li>
                </ul>
            </div>
        </div>
    </nav>
</header>

<div id="page" class="container-fluid">

    <div class="row-fluid">
        <div class="alert alert-info loginbox span10 offset1">
            <a href="<?php echo $CFG->wwwroot;?>/login" />
                <h2>
                    <img src="<?php echo $CFG->wwwroot; ?>/theme/clean/pix/01.png" alt="01" />
                    <strong>Acessar o <?php echo $SITE->shortname; ?></strong>
                </h2>
            </a>
        </div>
    </div>

    <div class="row-fluid cardscontainer">

        <div class="span2 card offset1">
            <a href="<?php echo get_config('theme_clean', 'faq'); ?>">
                <img src="<?php echo $CFG->wwwroot; ?>/theme/clean/pix/04.png" alt="01" />
                <h3>Perguntas frequentes</h3>
                <p>Veja respostas para as dúvidas frequentes</p>
            </a>
        </div>

        <div class="span2 card">
            <a href="<?php echo $CFG->wwwroot;?>/local/tutoriais">
                <img src="<?php echo $CFG->wwwroot; ?>/theme/clean/pix/06.png" alt="01" />
                <h3>Tutoriais</h3>
                <p>Aprenda mais sobre o Moodle e como nele realizar algumas tarefas</p>
            </a>
        </div>

        <div class="span2 card">
            <a href="<?php echo $CFG->wwwroot; ?>/local/tutoriais/atendimento.php">
                <img src="<?php echo $CFG->wwwroot; ?>/theme/clean/pix/02.png" alt="01" />
                <h3>Suporte a usuários</h3>
                <p>Saiba onde obter ajuda</p>
            </a>
        </div>

        <div class="span2 card">
            <a href="<?php echo $CFG->wwwroot; ?>/local/tutoriais/politicas_de_uso.php">
                <img src="<?php echo $CFG->wwwroot; ?>/theme/clean/pix/05.png" alt="01" />
                <h3>Políticas de uso</h3>
                <p>Conheça o propósito deste ambiente e as reponsabilidades dos usuários</p>
            </a>
        </div>

        <div class="span2 card">
            <a href="#">
                <img src="<?php echo $CFG->wwwroot; ?>/theme/clean/pix/03.png" alt="01" />
                <h3>Cursos livres</h3>
                <p>Cursos de acesso livre, abertos ao público</p>
                <!--
                <p>Navegue por cursos disponíveis para visitantes. Em breve!</p>
                -->
                <p>(Em breve)</p>
            </a>
        </div>

    </div>
    <div class="row-fluid prerodape">
        <div class="span10 offset1">
            <p>:: Este é o <?php echo $SITE->fullname; ?>. Consulte a <a href="<?php echo $CFG->wwwroot; ?>/local/tutoriais/implantacoes.php">lista de implantações de Moodle da UFSC</a> para ver outras opções.</p>
        </div>
    </div>
    <div id="page-content" class="row-fluid">
        <section id="region-main" class="span9<?php if ($left) { echo ' pull-right'; } ?>">
            <?php
            echo $OUTPUT->course_content_header();
            echo $OUTPUT->main_content();
            echo $OUTPUT->course_content_footer();
            ?>
        </section>
        <?php
        $classextra = '';
        if ($left) {
            $classextra = ' desktop-first-column';
        }
        echo $OUTPUT->blocks('side-pre', 'span3'.$classextra);
        ?>
    </div>

    <footer id="page-footer">
        <div id="course-footer"><?php echo $OUTPUT->course_footer(); ?></div>
        <p class="helplink"><?php echo $OUTPUT->page_doc_link(); ?></p>
        <?php
        echo $html->footnote;
        echo $OUTPUT->login_info();
        echo $OUTPUT->home_link();
        echo $OUTPUT->standard_footer_html();
        ?>
    </footer>

    <?php echo $OUTPUT->standard_end_of_body_html() ?>

</div>
</body>
</html>
