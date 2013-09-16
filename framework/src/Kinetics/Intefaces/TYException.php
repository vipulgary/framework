<?php
/*
 * Thought Yards the Innovation 
 * Interface for Exceptions
 * LICENCE - GNU - 
 * @author Vipul Dadhich <vipul.dadhich@gmail.com>
 * Interface Define here
 * Pre requisite for the WEB/CONSOLE Application for Task
 */
 
namespace ThoughtYards\Kinetics\Interfaces;

interface TYException
{
    /* Protected methods inherited from Exception class */
    public function getMessage();                 // Exception message 
    public function getCode();                    // User-defined Exception code
    public function getFile();                    // Source filename
    public function getLine();                    // Source line
    public function getTrace();                   // An array of the backtrace()
    public function getTraceAsString();           // Formated string of trace
    
    /* Overrideable methods inherited from Exception class */
    public function __toString();                 // formated string for display
    public function __construct($message = null, $code = 0);
}

?>