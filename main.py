from PySide6.QtWidgets import (
    QApplication, QWidget, QVBoxLayout, QPushButton, QFileDialog,
    QLabel, QTextEdit, QCheckBox
)
import sys

class HemoTrackApp(QWidget):
    def __init__(self):
        super().__init__()
        self.setWindowTitle("HEMOTRACK - Leitor de Exames")
        self.setMinimumSize(600, 500)

        # Layout principal
        layout = QVBoxLayout()

        # Checkbox de exame
        self.checkbox_hemograma = QCheckBox("HEMOGRAMA")
        layout.addWidget(self.checkbox_hemograma)

        # Botão de seleção de PDF
        self.btn_selecionar_pdf = QPushButton("Selecionar exame (PDF)")
        self.btn_selecionar_pdf.clicked.connect(self.selecionar_pdf)
        layout.addWidget(self.btn_selecionar_pdf)

        # Label de arquivo selecionado
        self.label_arquivo = QLabel("Nenhum arquivo selecionado.")
        layout.addWidget(self.label_arquivo)

        # Área de resultado
        self.resultado_output = QTextEdit()
        self.resultado_output.setReadOnly(True)
        layout.addWidget(self.resultado_output)

        self.setLayout(layout)
        self.caminho_pdf = None

    def selecionar_pdf(self):
        if not self.checkbox_hemograma.isChecked():
            self.resultado_output.setPlainText("Selecione pelo menos um tipo de exame (ex: Hemograma).")
            return

        file_path, _ = QFileDialog.getOpenFileName(self, "Selecione o PDF do exame", "", "PDF Files (*.pdf)")
        if file_path:
            self.caminho_pdf = file_path
            self.label_arquivo.setText(f"Arquivo selecionado:\n{file_path}")

            # Apenas simula a leitura (vamos fazer de verdade na próxima etapa)
            self.resultado_output.setPlainText("Leitura do exame em andamento...\n(Implementação será feita na próxima etapa)")

if __name__ == "__main__":
    app = QApplication(sys.argv)
    window = HemoTrackApp()
    window.show()
    sys.exit(app.exec())