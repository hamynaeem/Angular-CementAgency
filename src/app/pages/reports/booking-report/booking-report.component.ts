import { Component, OnInit, ViewChild } from '@angular/core';
import { Router } from '@angular/router';
import {
  GetDateJSON,
  JSON2Date
} from '../../../factories/utilities';
import { HttpBase } from '../../../services/httpbase.service';
import { PrintDataService } from '../../../services/print.data.services';

@Component({
  selector: 'app-booking-report',
  templateUrl: './booking-report.component.html',
  styleUrls: ['./booking-report.component.scss'],
})
export class BookingReportComponent implements OnInit {
  @ViewChild('RptTable') RptTable!: any;

  public Filter = {
    FromDate: GetDateJSON(),
    ToDate: GetDateJSON(),
    Balance: '0',
    RouteID: '',
  };

  public data: object[] = [];

  setting = {
    Checkbox: false,
    Columns: [
      {
      label: "Booking No",
      fldName: "BookingID",
    },
    {
      label: "Date",
      fldName: "Date",
    },
    {
      label: "Supplier Name",
      fldName: "CustomerName",
    },

    {
      label: "Invoice No",
      fldName: "InvoiceNo",
    },
    {
      label: "Vehicle No",
      fldName: "VehicleNo",
    },
    {
      label: "Builty No",
      fldName: "BuiltyNo",
    },
    {
      label: "Amount",
      fldName: "Amount",
      sum: true,

    },
    {
      label: "Discount",
      fldName: "Discount",
      sum: true,

    },
    {
      label: "Carriage",
      fldName: "Carriage",
      sum: true,

    },

    {
      label: "Net Amount",
      fldName: "NetAmount",
      sum: true,

    },

    {
      label: "Type",
      fldName: "DtCr",
    },
    {
      label: "Posted",
      fldName: "IsPosted",
    },
  ],

    Actions: [

    ],
    Data: [],
    SubTable: {
      table: 'details',

      Columns: [
        {
          label: 'Type',
          fldName: 'Type',
        },
        {
          label: 'Product Name',
          fldName: 'ProductName',
        },
        {
          label: 'PurchasePrice',
          fldName: 'Price',
        },
        {
          label: 'Purchase Qty',
          fldName: 'PurchaseQty',
          sum: true,
        },
        {
          label: 'Sale Qty',
          fldName: 'SaleQty',
          sum: true,
        },

        {
          label: 'Purchase Amount',
          fldName: 'PAmount',
          sum: true,
        },
        {
          label: 'Sale Amount',
          fldName: 'SAmount',
          sum: true,
        },
      ],
    },
  };

  public rowBackgroundConfig = {
    subRowBackgroundConfig: {
      // Multiple conditions with priority (as expected by getSubRowBackgroundColor)
      conditions: [

        {
            // Purchase type
            condition: (subRow: any) => subRow.Type === 'Purchase',
            color: '#ffcccc', // light red
            priority: 10
          },
          {
          // Sale type
          condition: (subRow: any) => subRow.Type === 'Sale',
          color: 'lightgreen',
          priority: 5
        }
      ],
      // Fallback single condition (optional, for backward compatibility)
      condition: (subRow: any) =>
        subRow.Type === 'Purchase' || subRow.Type === 'Sale',
      color: (subRow: any) =>
        subRow.Type === 'Purchase' ? 'red' : 'lightgreen',
      // Default color if no condition matches
      defaultColor: 'white'
    }
  };

  public toolbarOptions: object[] = [];
  constructor(
    private http: HttpBase,
    private ps: PrintDataService,
    private router: Router,

  ) {}

  ngOnInit() {
    this.FilterData();
  }
  PrintReport() {
    this.ps.PrintData.HTMLData = document.getElementById('print-section');
    this.ps.PrintData.Title = 'Booking Report';
    this.ps.PrintData.SubTitle =
      'From :' +
      JSON2Date(this.Filter.FromDate) +
      ' To: ' +
      JSON2Date(this.Filter.ToDate);

    this.router.navigateByUrl('/print/print-html');
  }
  FilterData() {
    let filter =
      "Date between '" +
      JSON2Date(this.Filter.FromDate) +
      "' and '" +
      JSON2Date(this.Filter.ToDate) +
      "'";

    this.http.getData('qrybooking?filter=' + filter).then((r: any) => {
      this.data = r.map((obj: any) => {
        return {
          ...obj,
          details: [],
          Status: obj.IsPosted == 0 ? 'Un-Posted' : 'Posted',
          Date: obj.Date.substring(0, 10),
        };
      });
    });
  }
  Clicked(e: any) {
    console.log(e);
    if (e.action === 'print') {
      this.router.navigateByUrl('/print/printinvoice/' + e.data.InvoiceID);
    }
  }

  RowClicked(event: any) {
    console.log(event);
    if (event.data.details.length == 0) {
      this.http
        .getData('qrybookingdetails?filter=BookingID=' + event.data.BookingID)
        .then((r) => {
          event.data['details'] = r;
        });
    }
  }
}
