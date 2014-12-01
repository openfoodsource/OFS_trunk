<?php

class output_Label
  {
    public $number_of_columns;
    public $label_width;
    public $horiz_spacing;
    public $number_of_rows;
    public $label_height;
    public $vert_spacing;
    public $page_top_margin;
    public $page_left_margin;
    public $label_margin;
    public $font_scaling;
    private $label_column;
    private $label_row;
    private $label_page;

    public function __construct ($number_of_columns, $label_width, $horiz_spacing, $number_of_rows, $label_height, $vert_spacing, $page_top_margin, $page_left_margin, $label_margin, $font_scaling)
      {
        $this->number_of_columns  = $number_of_columns;
        $this->label_width        = $label_width;
        $this->horiz_spacing      = $horiz_spacing;
        $this->number_of_rows     = $number_of_rows;
        $this->label_height       = $label_height;
        $this->vert_spacing       = $vert_spacing;
        $this->page_top_margin    = $page_top_margin;
        $this->page_left_margin   = $page_left_margin;
        $this->label_margin       = $label_margin;
        $this->font_scaling       = $font_scaling;
      }

    public function getLabelCSS ()
      {
        return '
          @media print {
            .labelpage
              {
              overflow:hidden;
              padding-top:'.number_format ($this->page_top_margin - $this->vert_spacing, 2).'in ;
              padding-left:'.number_format ($this->page_left_margin - $this->horiz_spacing, 2).'in;
              height:'.number_format (($this->label_height + $this->vert_spacing) * $this->number_of_rows, 2).'in;
              width:'.number_format (($this->label_width + $this->horiz_spacing) * $this->number_of_columns + $this->page_left_margin, 2).'in;
              page-break-after: always;
              }
            .labelrow
              {
              overflow:hidden;
              margin-left:'.number_format ($this->page_left_margin - $this->horiz_spacing, 2).'in;
              height:'.number_format ($this->label_height + $this->vert_spacing, 2).'in;
              width:100%;
              }
            .labelbody
              {
              float:left;
              overflow:hidden;
              margin-top:'.number_format ($this->vert_spacing, 2).'in;
              margin-left:'.number_format ($this->horiz_spacing, 2).'in;
              height:'.number_format ($this->label_height + $this->vert_spacing, 2).'in;
              width:'.number_format ($this->label_width - (2 * $this->label_margin), 2).'in;
              padding:'.$this->label_margin.'in;
              text-align:justify;
              }
            .testpage
              {
              background:#ccc;
              border:1px solid black;
              font-size:'.number_format (70 * $this->font_scaling, 0).'%
              }
            }
          @media screen {
            .labelrow
              {
              overflow:hidden;
              margin-left:'.number_format ($this->page_left_margin - $this->horiz_spacing, 2).'in;
              height:'.number_format ($this->label_height + $this->vert_spacing, 2).'in;
              width:100%;
              }
            .labelbody
              {
              float:left;
              overflow:hidden;
              margin-top:'.number_format ($this->vert_spacing, 2).'in;
              margin-left:'.number_format ($this->horiz_spacing, 2).'in;
              height:'.number_format ($this->label_height + $this->vert_spacing, 2).'in;
              width:'.number_format ($this->label_width - (2 * $this->label_margin), 2).'in;
              padding:'.$this->label_margin.'in;
              text-align:justify;
              }
            .testpage
              {
              background:#ccc;
              border:1px solid black;
              font-size:'.number_format (70 * $this->font_scaling, 0).'%
              }
            }';
      }

    // This function will return markup code starts a label sheet.
    public function beginLabelSheet ()
      {
        $this->label_column = 1;
        $this->label_row = 1;
        return  '
            <div class="labelpage">
              <div class="labelrow">
                <div class="labelbody">';
      }

    // This function will return markup code closes a label sheet.
    public function finishLabelSheet ()
      {
        return '
                </div>
              </div>
            </div>';
      }

    // This function will return markup code that will advance labels to
    // the next location (left to right, top to bottom).
    public function advanceLabel ()
      {
        $return = '
                </div>';
        if (++$this->label_column > $this->number_of_columns)
          {
            $this->label_column = 1;
            $return .= '
                  </div>';
            if (++$this->label_row > $this->number_of_rows)
              {
                $this->label_row =1;
                ++$this->label_page;
                $return .= '
                    </div>
                    <div class="labelpage">';
              }
            $return .= '
                  <div class="labelrow">';
          }
        $return .= '
                <div class="labelbody">';
        return $return;
      }

    // This function will return markup code that will advance labels from
    // anywhere in a row to the next row.
    public function advanceLabelRow ()
      {
        $return = '';
        $this->label_column = 1;
        $return .= '
                </div>
              </div>';
        if (++$this->label_row > $this->number_of_rows)
          {
            $this->label_row =1;
            ++$this->label_page;
            $return .= '
                </div>
                <div class="labelpage">';
          }
        $return .= '
              <div class="labelrow">
                <div class="labelbody">';
        return $return;
      }

    // This function will return markup code that will advance labels from
    // anywhere on a page to the next page.
    public function advanceLabelPage ()
      {
        $return = '';
        $this->label_column = 1;
        $this->label_row =1;
        ++$this->label_page;
        $return .= '
                </div>
              </div>
            </div>
            <div class="labelpage">
              <div class="labelrow">
                <div class="labelbody">';
        return $return;
      }

    // This function will return markup code that will print labels down the
    // left side and across the bottom of a sheet, for label alignment checks.
    public function printAlignmentPage ()
      {
        $row_count = $this->number_of_rows;
        $col_count = $this->number_of_columns;
        $return = '';
        $test_block = '
          <div class="testpage">Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Maecenas diam purus, fermentum vitae, tempus id, placerat at, dui. Ut vel orci et enim pellentesque pretium. Duis aliquam nulla vel orci. Sed sit amet ante a sem sodales bibendum. Curabitur faucibus bibendum erat. Suspendisse dolor est, mollis sit amet, lobortis in, blandit ultricies, urna. Nulla mollis nunc ac quam. Duis id orci non turpis elementum iaculis. Sed porttitor tincidunt pede. Nulla non justo id augue pretium porttitor. Suspendisse potenti. Suspendisse blandit, elit sit amet consectetuer accumsan, turpis ante facilisis enim, nec pellentesque magna purus id orci.<br>
          Praesent tortor mauris, mollis non, tempor vitae, aliquet vitae, augue. Nullam diam. Vestibulum et urna. Nunc in sapien. In elementum est et diam. Vivamus nulla tellus, porta eget, tempus eget, fringilla sed, lacus. Suspendisse purus libero, consequat eu, lobortis non, luctus in, sapien. Aenean quam eros, rhoncus vel, iaculis et, scelerisque in, magna. Aenean hendrerit, odio id mollis varius, urna pede vulputate massa, ac blandit arcu justo laoreet purus. Integer et felis at lectus commodo consequat. Vestibulum a arcu. Donec est ligula, viverra ac, tempor sit amet, ultrices quis, pede.</div>';
        $return .= '<style>'.$this->getLabelCSS().'
          </style>';
        $return .= $this->beginLabelSheet();
        while (--$row_count > 0)
          {
            $return .= $test_block;
            $return .= $this->advanceLabelRow();
          }
        while (--$col_count > 0)
          {
            $return .= $test_block;
            $return .= $this->advanceLabel();
          }
        $return .= $test_block;
        $return .= $this->finishLabelSheet();
        return $return;
      }

    // This function will take label information from the label cookie and
    // generate the label object to be used.
    public function cookieToLabel ($label_name)
      {
        list ($number_of_columns,
              $label_width,
              $horiz_spacing,
              $number_of_rows,
              $label_height,
              $vert_spacing,
              $page_top_margin,
              $page_left_margin,
              $label_margin,
              $font_scaling) = explode ('~', $_COOKIE[$label_name]);
        $new_label = new output_Label ($number_of_columns, $label_width, $horiz_spacing, $number_of_rows, $label_height, $vert_spacing, $page_top_margin, $page_left_margin, $label_margin, $font_scaling);
        return $new_label;
      }

  }

?>