import { Component, OnInit, ViewChild } from '@angular/core';
import { ActivatedRoute, Params, Router } from '@angular/router';
import { Buttons } from '../../../../../../../libs/future-tech-lib/src/lib/components/navigator/navigator.component';
import {
  GetDateJSON,
  JSON2Date,
  getCurDate,
} from '../../../factories/utilities';
import { HttpBase } from '../../../services/httpbase.service';
import { MyToastService } from '../../../services/toaster.server';
import { VoucherModel } from '../voucher.model';

@Component({
  selector: 'app-cash-payment',
  templateUrl: './cash-payment.component.html',
  styleUrls: ['./cash-payment.component.scss'],
})
export class CashPaymentComponent implements OnInit {
  @ViewChild('cmbCustomer') cmbCustomer: any;
  public Voucher = new VoucherModel();
  Customers = [];
  AcctTypes = [];
  EditID = '';
  public Ino = '';

  curCustomer: any = {};
  constructor(
    private http: HttpBase,
    private alert: MyToastService,
    private router: Router,
    private activatedRoute: ActivatedRoute
  ) {}

  ngOnInit() {
    this.LoadCustomer('');

    this.activatedRoute.params.subscribe((params: Params) => {
      if (params.EditID) {
        this.EditID = params.EditID;
        this.Ino = this.EditID;
        this.http
          .getData('qryvouchers?filter=VoucherID=' + this.EditID)
          .then((r: any) => {
            this.Voucher = r[0];
            // Convert date to proper format for HTML date input (YYYY-MM-DD)
            const dateObj = new Date(r[0].Date);
            this.Voucher.Date = dateObj.toISOString().slice(0, 10);
            this.LoadCustomer({ AcctTypeID: r[0].AcctTypeID });
            this.GetCustomer(this.Voucher.CustomerID);
          });
      } else {
        this.EditID = '';
        // Set today's date for new records
        const today = new Date();
        this.Voucher.Date = today.toISOString().slice(0, 10);
      }
      console.log(this.EditID);
    });
  }
  async FindINo() {
    let voucher: any = await this.http.getData('vouchers/' + this.Ino);
    if (voucher.Credit > 0)
      this.router.navigate(['/cash/cashreceipt/', this.Ino]);
    else this.router.navigate(['/cash/cashpayment/', this.Ino]);
  }
  LoadCustomer(event: any) {
    this.http
      .getData(
        'qrycustomers?flds=CustomerName,Address, Balance, CustomerID&orderby=CustomerName'
      )
      .then((r: any) => {
        this.Customers = r;
      });
  }
  SaveData() {
    let voucherid = '';
    // Coerce PrevBalance to a valid number (handles string values returned from API)
    let prevBalance = 0;
    if (
      this.curCustomer &&
      this.curCustomer.Balance !== undefined &&
      this.curCustomer.Balance !== null
    ) {
      prevBalance = Number(this.curCustomer.Balance);
      if (isNaN(prevBalance)) {
        console.warn('curCustomer.Balance is not a valid number:', this.curCustomer.Balance);
        prevBalance = 0;
      }
    }
    this.Voucher.PrevBalance = prevBalance;
    
    // Set default values for required fields if missing
    if (!this.Voucher.RefID) this.Voucher.RefID = "0";
    if (!this.Voucher.IsPosted) this.Voucher.IsPosted = 0;
    if (!this.Voucher.FinYearID) this.Voucher.FinYearID = 1;
    if (!this.Voucher.RefType) this.Voucher.RefType = 0;
    if (!this.Voucher.RouteID) this.Voucher.RouteID = "0";
    if (!this.Voucher.AcctTypeID) this.Voucher.AcctTypeID = "0";
    if (!this.Voucher.SalesmanID) this.Voucher.SalesmanID = "0";
    if (!this.Voucher.UserID) this.Voucher.UserID = "0";
    if (!this.Voucher.ProductID) this.Voucher.ProductID = "0";
    if (!this.Voucher.ClosingID) this.Voucher.ClosingID = "0";
    
    // Add BusinessID to the voucher object (required by backend)
    (this.Voucher as any).BusinessID = 1;
    
    // Format date for PHP backend (Y-m-d format)
    if (this.Voucher.Date) {
      let dateObj: Date;
      if (typeof this.Voucher.Date === 'object') {
        const d: any = this.Voucher.Date;
        dateObj = new Date(d.year, d.month - 1, d.day);
      } else {
        dateObj = new Date(this.Voucher.Date);
      }
      if (isNaN(dateObj.getTime())) {
        this.alert.Error('Invalid date format', 'Error');
        return;
      }
      this.Voucher.Date = dateObj.toISOString().slice(0, 10);
    } else {
      this.alert.Error('Date is required', 'Error');
      return;
    }
    
    // Check for other required fields
    if (!this.Voucher.CustomerID) {
      this.alert.Error('Please select a customer', 'Error');
      return;
    }
    if (!this.Voucher.Description) {
      this.alert.Error('Description is required', 'Error');
      return;
    }
    if (!this.Voucher.Debit && !this.Voucher.Credit) {
      this.alert.Error('Either Debit or Credit amount is required', 'Error');
      return;
    }
    
    // Ensure numeric values are properly set
    this.Voucher.Debit = this.Voucher.Debit || 0;
    this.Voucher.Credit = this.Voucher.Credit || 0;
    
    if (this.EditID != '') {
      voucherid = '/' + this.EditID;
    }

    console.log('Sending voucher data:', this.Voucher);
      const url = 'vouchers' + voucherid;
      this.http
        .postTask(url, this.Voucher)
        .then((r) => {
        this.alert.Sucess('Payment Saved', 'Save', 1);
        if (this.EditID != '') {
          this.router.navigateByUrl('/cash/cashpayment/');
        } else {
          this.Voucher = new VoucherModel();
          // Set today's date for new record
          const today = new Date();
          this.Voucher.Date = today.toISOString().slice(0, 10);
          // Safely focus the customer select/component if available. Different
          // select components expose different focus APIs (focus, focusIn,
          // or nativeElement.focus()). Try them defensively.
          if (this.cmbCustomer) {
            try {
              if (typeof this.cmbCustomer.focus === 'function') {
                this.cmbCustomer.focus();
              } else if (typeof this.cmbCustomer.focusIn === 'function') {
                this.cmbCustomer.focusIn();
              } else if (
                this.cmbCustomer.nativeElement &&
                typeof this.cmbCustomer.nativeElement.focus === 'function'
              ) {
                setTimeout(() => this.cmbCustomer.nativeElement.focus(), 0);
              }
            } catch (e) {
              console.warn('Could not set focus on cmbCustomer', e);
            }
          }
        }
      })
      .catch((err) => {
        // Don't reset the date on error - keep user's input
        console.log(err);
        const errorMessage = err.error?.message || err.error?.msg || 'Failed to save data';
        this.alert.Error(errorMessage, 'Error');
      });
  }
  GetCustomer(CustomerID: string) {
    console.log(CustomerID);
    if (CustomerID && CustomerID !== '') {
      this.http
        .getData('qrycustomers?filter=CustomerID=' + CustomerID)
        .then((r: any) => {
          this.curCustomer = r[0];
        });
    }
  }
  Round(amnt: number) {
    const n = Number(amnt);
    if (isNaN(n)) return 0;
    return Math.round(n);
  }
  NavigatorClicked(e: any) {
    let billNo = 240000001;
    switch (Number(e.Button)) {
      case Buttons.First:
        this.http.getData('getvouchno/P/0/F').then((r: any) => {
          this.router.navigateByUrl('/cash/cashpayment/' + r.Vno);
        });
        break;
      case Buttons.Previous:
        this.http
          .getData('getvouchno/P/' + this.EditID + '/B')
          .then((r: any) => {
            this.router.navigateByUrl('/cash/cashpayment/' + r.Vno);
          });
        break;
      case Buttons.Next:
        this.http
          .getData('getvouchno/P/' + this.EditID + '/N')
          .then((r: any) => {
            this.router.navigateByUrl('/cash/cashpayment/' + r.Vno);
          });
        break;
      case Buttons.Last:
        this.http.getData('getvouchno/P/0/L').then((r: any) => {
          this.router.navigateByUrl('/cash/cashpayment/' + r.Vno);
        });
        break;
      default:
        break;
    }
    //this.router.navigateByUrl('/sale/wholesale/' + billNo);
  }
  Add() {
    this.router.navigateByUrl('/cash/cashpayment');
  }
  Cancel() {
    this.Voucher = new VoucherModel();
    this.router.navigateByUrl('/cash/cashpayment');
  }
}
