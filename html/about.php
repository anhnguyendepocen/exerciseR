<?php
# Loading required config
function __autoload($name) {
    $file = sprintf("php/%s.php", $name);
    try {
        require($file);
    } catch (Exception $e) {
        throw new MissingException("Unable to load \"" . $file . "\".");
    }
}

// Loading configuration
$config = new ConfigParser("../files/config.ini");

# Loading the exercise class
$Handler = new ExerciseR($config, true);
$Handler->site_show_header();
?>

    <div class="row">
        <div class="col-sm-12">
            <h1>About</h1>

            <h3>What is the <b>exerciseR</b>?</h3>

            Currently exerciseR is a proof of concept.
            The idea is to provide an interactive user interface
            for students taking the course "Introduction to Programming with R"
            where they can submit solutions for some practical exercises.

            <h3>How does it work?</h3>

            Different exercises can be assigned to the participants.
            After reading the instructions the participants can develop
            some R code on their local machines. As soon as they solved
            the problem they can upload their scrpit/code.
            <b>exerciseR</b> runs the code and checks whether:
            
            <ul>
                <li>the scrpit can be executed or not</li>
                <li>the solution is correct</li>
            </ul>
            
            The participant will get immediate feedback (error/success)
            and the complete log to check what is going wrong, if something
            is going wrong. Once succesfully solved an exercise the exercise
            will be marked as <span class="text-success">solved</span>.

            <h3>How does it technically work?</h3>

            Every time a user runs a script the <b>exerciseR</b> 
            starts a docker instance (currently <i>r-base</i> only!)
            and compares the solution of the participants to the correct
            solution. If the script crashes as some point an error will be
            returned. If the solution of the participant is not correct,
            an error will be returned as well. Else, the exercise will
            be marked as <span class="text-success">solved</span>.

            <h3>Developer/Maintainer</h3>
            
            <b>Reto Stauffer</b><br />
            University of Innsbruck<br />
            Innsbruck, Austria<br />
            <br />
            <b>Contact:</b><br />
            <ul>
                <li>e-Mail: <a href="&#109;&#97;&#105;&#108;&#116;&#111;&#58;&#82;&#101;&#116;&#111;&#46;&#83;&#116;&#97;&#117;&#102;&#102;&#101;&#114;&#64;&#117;&#105;&#98;&#107;&#46;&#97;&#99;&#46;&#97;&#116;">&#82;&#101;&#116;&#111;&#46;&#83;&#116;&#97;&#117;&#102;&#102;&#101;&#114;&#64;&#117;&#105;&#98;&#107;&#46;&#97;&#99;&#46;&#97;&#116;</a></li>
                <li>website: <a href="https://retostauffer.org">https://retostauffer.org</a></li>
                <li>github: <a href="https://github.com/retostauffer/exerciseR">exerciseR repository</a></li>
            </ul>
        </div>

    </div>

<?php $Handler->site_show_footer(); ?>
