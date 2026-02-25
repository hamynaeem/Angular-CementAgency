import {
  AfterViewInit,
  Component,
  Input,
  OnChanges,
  OnInit,
  SimpleChanges,
  TemplateRef,
  ViewChild,
} from '@angular/core';
import { ActivatedRoute, Params, Router } from '@angular/router';
import html2canvas from 'html2canvas';
import * as JSPDF from 'jspdf';
import { LocalDataSource } from 'ng2-smart-table';
import { BsModalRef, BsModalService } from 'ngx-bootstrap/modal';
import { ButtonsBarComponent } from '../../../../../../../libs/future-tech-lib/src/lib/components/buttons-bar/buttons-bar.component';
import {
  getCurDate,
  GetDateJSON,
  JSON2Date,
} from '../../../factories/utilities';
import { CachedDataService } from '../../../services/cacheddata.service';
import { HttpBase } from '../../../services/httpbase.service';
import { MyToastService } from '../../../services/toaster.server';
interface Booking {
  Date: any;
  DtCr: string;
  InvoiceNo?: string;
  VehicleNo?: string;
  CofNo?: string;
  ReceiptNo?: string;
  BuiltyNo?: string;
  BagsPurchase?: number;
  BagsSold?: number;
  SupplierID?: string;
  Amount?: number;
  Carriage?: number;
  NetAmount?: number;
}

interface BookingDetail {
  ProductID?: string;
  ProductName: string;
  Qty: number;
  Price: number;
  Amount: number;
  NetAmount?: number;
  Carriage?: number;
  Packing: number;
}

interface Sale {
  TotalAmount?: number;
  Discount?: number;
  NetAmount?: number;
  Received?: number;
  Credit?: number;
}

interface SaleDetail {
  CustomerID?: string;
  ProductID?: string;
  Qty: number;
  Price: number;
  Discount: number;
  MRP: number;
  Received: number;
  Amount: number;
  ProductName?: string;
  CustomerName?: string;
  OrderID?: string;
}

@Component({
  selector: 'app-booking-invoice',
  templateUrl: './booking-invoice.component.html',
  styleUrls: ['./booking-invoice.component.scss'],
})
export class BookingInvoiceComponent
  implements OnInit, OnChanges, AfterViewInit
{
  @Input() Type!: string;
  @Input() EditID = '';

  selectedProduct: any;
  @ViewChild('fromBooking') fromBooking!: any;
  @ViewChild('cmbProduct') cmbProd!: any;
  @ViewChild('qty') elQty!: any;
  @ViewChild('cmbCustomers') cmbCustomers!: any;
  @ViewChild('btnBar') btnBar!: ButtonsBarComponent;
  @ViewChild('ordersModalTemplate') ordersModalTemplate!: TemplateRef<any>;

  public data = new LocalDataSource([]);
  Ino = '';
  tqty: any = '';
  public btnsave = false;
  public isPosted = false;

  // Modal reference
  modalRef?: BsModalRef;

  public booking!: Booking;
  bookingDetail!: BookingDetail;

  sale!: Sale;
  saleDetail!: SaleDetail;

  public Products: any = [];
  public SelectedProduct: any = {};
  public bookData: BookingDetail[] = [];
  public saleData: SaleDetail[] = [];
  public Stores: any = [];
  public Customers: any = [];
  public Suppliers: any = [];

  // Orders modal properties
  public confirmedOrders: any[] = [];
  public loadingOrders: boolean = false;

  $Companies: any;
  AcctTypes: any;
  SelectCust: any = {};
  btnLocked: boolean = true;

  constructor(
    private http: HttpBase,
    private cachedData: CachedDataService,
    private myToaster: MyToastService,
    private activatedRoute: ActivatedRoute,
    private router: Router,
    private modalService: BsModalService
  ) {
    this.AcctTypes = this.cachedData.AcctTypes$;
    this.$Companies = this.cachedData.Stores$;
  }

  ngOnInit() {
    this.Cancel();
    this.cachedData.Products$.subscribe((products: any[]) => {
      this.Products = products;
    });
    this.cachedData.Accounts$.subscribe((accounts: any[]) => {
      this.Customers = accounts.filter(
        (c) => c.AcctType.toUpperCase() === 'CUSTOMERS'
      );
      this.Suppliers = accounts.filter(
        (c) => c.AcctType.toUpperCase() === 'SUPPLIERS'
      );
    });
    this.activatedRoute.params.subscribe((params: Params) => {
      if (params.EditID) {
        this.EditID = params.EditID;

        this.LoadInvoice();
      }
    });
  }

  Cancel() {
    this.saleDetail = {
      Qty: 0,
      Price: 0,
      Discount: 0,
      MRP: 0,
      Received: 0,
      Amount: 0,
    };
    this.bookingDetail = {
      Qty: 0,
      Price: 0,
      Amount: 0,
      ProductName: '',
      Packing: 0,
    };
    this.booking = {
      DtCr: 'CR',
      Date: GetDateJSON(new Date(getCurDate())),
      BagsPurchase: 0,
      BagsSold: 0,
      Amount: 0,
      Carriage: 0,
      NetAmount: 0,
    };
    this.sale = {
      TotalAmount: 0,
      Discount: 0,
      NetAmount: 0,
      Received: 0,
      Credit: 0,
    };
  }
  ngOnChanges(_changes: SimpleChanges) {}
  ngAfterViewInit(): void {}
  async SaveData(navigateAfterSave: boolean = true, clearAfterSave: boolean = true): Promise<any> {
    if (!this.booking || !this.bookData.length) {
      this.myToaster.Error(
        'Booking and booking details are required.',
        'Validation Error'
      );
      return Promise.reject('validation:missing-data');
    }

    if (this.booking.BagsPurchase != this.booking.BagsSold) {
      this.myToaster.Error(
        'Bags Purchased and Bags Sold must be equal.',
        'Validation Error'
      );
      return Promise.reject('validation:bags-mismatch');
    }

    if (this.fromBooking.valid) {
      // Save booking data
      const data = {
        ...this.booking,
        details: this.bookData,
        sales: this.saleData,
      };

      data.Date = JSON2Date(data.Date);

      try {
        const r = await this.http
          .postTask('booking' + (this.EditID != '' ? '/' + this.EditID : ''), data);
        console.log(r);

        this.myToaster.Sucess('Booking saved successfully.', 'Success');
        // Only clear UI and navigate when requested. For PDF download we
        // want to save on server but keep the current UI so we can render it.
        if (clearAfterSave) {
          this.saleData = [];
          this.bookData = [];
          this.calcBooking();
          this.calcSaleData();
          this.Cancel();
          this.EditID = '';
        }
        if (navigateAfterSave) {
          this.router.navigateByUrl('/purchase/booking');
        }
        return r;
      } catch (err) {
        this.myToaster.Error('Failed to save booking.', 'Error');
        return Promise.reject(err);
      }
    } else {
      this.myToaster.Error(
        'Please fill all required fields.',
        'Validation Error'
      );
      return Promise.reject('validation:invalid-form');
    }
  }

  FindINo() {
    this.router.navigate(['/purchase/invoice/', this.Ino]);
  }
  addItem() {
    if (
      !this.bookingDetail.ProductID ||
      this.bookingDetail.Qty <= 0 ||
      this.bookingDetail.Price <= 0
    ) {
      this.myToaster.Error(
        'Please select a product and enter valid quantity and price.',
        'Validation Error'
      );
      return;
    }

    const newItem = {
      ProductID: this.bookingDetail.ProductID,
      ProductName:
        this.Products.find(
          (p: any) => p.ProductID === this.bookingDetail.ProductID
        )?.ProductName || '',
      Qty: this.bookingDetail.Qty,
      Price: this.bookingDetail.Price,
      Packing: this.selectedProduct?.Packing || 1,
      Amount: this.bookingDetail.Qty * this.bookingDetail.Price,
    };
    this.bookData.push(newItem);
    this.calcBooking();
    this.bookingDetail.ProductID = undefined;
    this.bookingDetail.Qty = 0;
    this.bookingDetail.Price = 0;
    // Safely focus the product select if available. Different select implementations
    // expose different APIs (`focus`, `focusIn`, or `nativeElement.focus`).
    if (this.cmbProd) {
      try {
        if (typeof this.cmbProd.focus === 'function') {
          this.cmbProd.focus();
        } else if (typeof this.cmbProd.focusIn === 'function') {
          this.cmbProd.focusIn();
        } else if (
          this.cmbProd.nativeElement &&
          typeof this.cmbProd.nativeElement.focus === 'function'
        ) {
          setTimeout(() => this.cmbProd.nativeElement.focus(), 0);
        }
      } catch (e) {
        console.warn('Could not focus cmbProd', e);
      }
    }
  }
  removeItem() {
    if (this.bookData.length > 0) {
      this.bookData.pop();
    }
  }
  deleteItem(index: number) {
    if (index > -1 && index < this.bookData.length) {
      this.bookData.splice(index, 1);
      this.calcBooking();
    }
  }
  calcBooking() {
    this.booking.Amount = this.bookData.reduce(
      (acc, item) => acc + item.Amount * 1,
      0
    );
    this.booking.NetAmount =
      this.booking.Amount + (this.booking.Carriage ?? 0) * 1;

    this.booking.BagsPurchase =
      this.bookData.reduce(
        (acc, item) => acc + item.Qty * (item.Packing ? item.Packing : 1),
        0
      ) * 20;
  }
  addSaleItem() {
    if (
      !this.saleDetail.ProductID ||
      this.saleDetail.Qty <= 0 ||
      this.saleDetail.Price <= 0
    ) {
      this.myToaster.Error(
        'Please select a product and enter valid quantity and price.',
        'Validation Error'
      );
      return;
    }

    const saleItem = {
      ProductID: this.saleDetail.ProductID,
      ProductName:
        this.Products.find(
          (p: any) => p.ProductID === this.saleDetail.ProductID
        )?.ProductName || '',
      Qty: this.saleDetail.Qty,
      Discount: this.saleDetail.Discount,
      MRP: this.saleDetail.MRP,
      Received: this.saleDetail.Received,
      Price: this.saleDetail.Price,
      Amount: this.saleDetail.Qty * this.saleDetail.Price,
      CustomerID: this.saleDetail.CustomerID || '',
      OrderID: this.saleDetail.OrderID || '0',
      CustomerName:
        this.Customers.find(
          (c: any) => c.CustomerID === this.saleDetail.CustomerID
        )?.CustomerName || '',
    };

    if (!this.saleData) {
      this.saleData = [];
    }
    this.saleData.push(saleItem);
    this.calcSaleData();
    // Safely focus the customer select if available
    if (this.cmbCustomers) {
      try {
        if (typeof this.cmbCustomers.focus === 'function') {
          this.cmbCustomers.focus();
        } else if (typeof this.cmbCustomers.focusIn === 'function') {
          this.cmbCustomers.focusIn();
        } else if (
          this.cmbCustomers.nativeElement &&
          typeof this.cmbCustomers.nativeElement.focus === 'function'
        ) {
          setTimeout(() => this.cmbCustomers.nativeElement.focus(), 0);
        }
      } catch (e) {
        console.warn('Could not focus cmbCustomers', e);
      }
    }
    this.saleDetail.ProductID = undefined;
    this.saleDetail.Qty = 0;
    this.saleDetail.Price = 0;
    this.saleDetail.Amount = 0;
    this.saleDetail.CustomerID = undefined;
  }
  calcSaleData() {
    this.sale.TotalAmount = this.saleData.reduce(
      (acc, item) => acc + item.Amount * 1,
      0
    );
    this.sale.Received = this.saleData.reduce(
      (acc, item) => acc + (item.Received ?? 0) * 1,
      0
    );
    this.sale.Discount = this.saleData.reduce(
      (acc, item) => acc + (item.Discount ?? 0) * 1,
      0
    );
    this.sale.NetAmount =
      (this.sale.TotalAmount ?? 0) - (this.sale.Discount ?? 0) * 1;
    this.sale.Credit =
      (this.sale.NetAmount ?? 0) - (this.sale.Received ?? 0) * 1;
    this.booking.BagsSold = this.saleData.reduce(
      (acc, item) => acc + item.Qty * 1,
      0
    );
  }
  saveAndPrint() {
    // Save but don't clear/navigate so the UI remains visible for printing.
    this.SaveData(false, false)
      .then(() => {
        setTimeout(() => {
          window.print();
          // After printing, clear and navigate back to list
          setTimeout(() => {
            this.saleData = [];
            this.bookData = [];
            this.calcBooking();
            this.calcSaleData();
            this.Cancel();
            this.EditID = '';
            this.router.navigateByUrl('/purchase/booking');
          }, 600);
        }, 200);
      })
      .catch((err) => {
        console.warn('Save before print failed or cancelled:', err);
      });
  }

  /**
   * Print current invoice view without saving to server.
   * This prints all displayed items (booking and sale items) as-is.
   */
  printPreview() {
    try {
      // ensure UI updated
      setTimeout(() => {
        window.print();
      }, 100);
    } catch (err) {
      console.warn('Print preview failed', err);
      this.myToaster.Error('Unable to open print preview', 'Error');
    }
  }

  downloadPdf() {
    // Save current data but don't navigate away, then render the visible
    // booking form to PDF and download it.
    this.SaveData(false, false)
      .then((r: any) => {
        setTimeout(() => {
          const data: any = document.getElementById('print-section');
          if (!data) {
            this.myToaster.Error('Print section not found', 'Error');
            return;
          }
          html2canvas(data).then((canvas) => {
            const imgWidth = 208;
            const imgHeight = (canvas.height * imgWidth) / canvas.width;
            const contentDataURL = canvas.toDataURL('image/png');
            const pdf = new JSPDF.jsPDF('p', 'mm', 'a4');
            let position = 0;
            pdf.addImage(contentDataURL, 'PNG', 0, position, imgWidth, imgHeight);
            const idPart = r && (r.BookingID || r.id) ? '-' + (r.BookingID || r.id) : '';
            const fileName = 'Booking' + idPart + '.pdf';
            pdf.save(fileName);
          });
        }, 300);
      })
      .catch((err) => {
        console.warn('Save before PDF failed or cancelled:', err);
        this.myToaster.Error('Failed to save booking; PDF not generated', 'Error');
      });
  }
  LoadInvoice() {
    if (this.EditID == '') {
      return;
    }
    this.http.getData('booking/' + this.EditID).then((r: any) => {
      if (r) {
        r.Date = GetDateJSON(new Date(r.Date));
        this.booking = r;
        if (r.IsPosted == 1) {
          this.myToaster.Error('Can not edit posted invoice', 'Edit', 1);
          this.router.navigateByUrl('/purchase/booking');
          return;
        }
        this.http
          .getData('qrybookingpurchase', {
            filter: 'BookingID = ' + this.EditID,
          })
          .then((d: any) => {
            if (d) {
              this.bookData = d;
              this.calcBooking();
            }
          });
        this.http
          .getData('qrybookingsale', { filter: 'BookingID = ' + this.EditID })
          .then((d: any) => {
            if (d) {
              this.saleData = d;
              this.calcSaleData();
            }
          });
        this.myToaster.Sucess('Invoice Loaded Successfully', 'Edit', 1);
      } else {
        this.myToaster.Warning('Invoice Not Found', 'Edit', 1);
        this.router.navigateByUrl('/purchase/booking');
      }
    });
  }
  deleteSaleItem(idx: number){
    this.saleData.splice(idx, 1);
    this.calcSaleData();
  }
  LoadOrders() {
    this.loadingOrders = true;
    this.confirmedOrders = [];

    // Load confirmed orders from qryorders table
    const filter = "Status='Confirmed'";
    const fields = 'OrderID,OrderDate,CustomerID,CustomerName,ProductID,ProductName,Quantity,Rate,Total,DeliveryAddress';

    this.http.getData(`qryorders?filter=${filter}&flds=${fields}&orderby=OrderDate desc`)
      .then((orders: any) => {
        this.confirmedOrders = orders || [];

        // Show the modal using BsModalService
        this.modalRef = this.modalService.show(this.ordersModalTemplate, {
          class: 'modal-lg',
          backdrop: 'static',
          keyboard: false
        });
      })
      .catch((error) => {
        console.error('Error loading confirmed orders:', error);
        this.myToaster.Error('Failed to load confirmed orders', 'Error');
        this.confirmedOrders = [];
      })
      .finally(() => {
        this.loadingOrders = false;
      });
  }

  selectOrder(order: any) {
    try {
      // Fill the sale form with selected order data
      this.saleDetail.CustomerID = order.CustomerID;
      this.saleDetail.ProductID = order.ProductID;
      this.saleDetail.OrderID = order.OrderID;
      this.saleDetail.Qty = order.Quantity;
      this.saleDetail.Price = order.Rate;
      this.saleDetail.Amount = order.Total;

      // Find and set the customer name for display
      const selectedCustomer = this.Customers.find((c: any) => c.CustomerID == order.CustomerID);
      if (selectedCustomer) {
        this.saleDetail.CustomerName = selectedCustomer.CustomerName;
      }

      // Find and set the product name for display
      const selectedProduct = this.Products.find((p: any) => p.ProductID == order.ProductID);
      if (selectedProduct) {
        this.saleDetail.ProductName = selectedProduct.ProductName;
      }

      // Store the order reference for potential status updates
      this.saleDetail.OrderID = order.OrderID;

      // Close the modal
      this.modalRef?.hide();

      // Show success message
      this.myToaster.Sucess(`Order #${order.OrderID} data loaded successfully`, 'Order Selected');

    } catch (error) {
      console.error('Error selecting order:', error);
      this.myToaster.Error('Failed to load order data', 'Error');
    }
  }

  closeModal() {
    this.modalRef?.hide();
  }

  // Helper methods used by template to resolve display names.
  getSupplierName(id: any): string {
    if (!id || !this.Suppliers) return '';
    const s = this.Suppliers.find((x: any) => x.CustomerID == id);
    return s ? s.CustomerName : '';
  }

  getCustomerName(id: any): string {
    if (!id || !this.Customers) return '';
    const c = this.Customers.find((x: any) => x.CustomerID == id);
    return c ? c.CustomerName : '';
  }

  getProductName(id: any): string {
    if (!id || !this.Products) return '';
    const p = this.Products.find((x: any) => x.ProductID == id);
    return p ? p.ProductName : '';
  }
}
