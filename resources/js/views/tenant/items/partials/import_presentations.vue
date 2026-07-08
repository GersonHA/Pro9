<template>
  <el-dialog
    :title="title"
    :visible.sync="showDialog"
    @close="close"
    @open="create"
    width="400px"
  >
    <div class="form-body">
      <div class="row">
        <div class="col-md-12 text-center">
          <div class="form-group">
            <label class="control-label mb-2">Seleccionar Archivo Excel</label>

            <el-upload
              class="upload-demo"
              ref="upload"
              :action="url"
              :headers="headers"
              :on-success="onSuccess"
              :on-error="onError"
              :limit="1"
              :show-file-list="true"
              :auto-upload="false"
              accept=".xlsx, .xls"
            >
              <el-button slot="trigger" type="primary">
                <i class="fa fa-upload"></i> Seleccione un archivo (xlsx)
              </el-button>
            </el-upload>
          </div>
        </div>
      </div>
    </div>

    <span slot="footer" class="dialog-footer">
      <el-button @click="close">Cancelar</el-button>
      <el-button type="primary" @click="submitUpload">Procesar</el-button>
    </span>
  </el-dialog>
</template>

<script>
export default {
  props: ["showDialog"],
  data() {
    return {
      title: "Importar Presentaciones",
      url: "/items/import_presentations",
      headers: headers_token,
    };
  },
  methods: {
    create() {
      if (this.$refs.upload) {
        this.$refs.upload.clearFiles();
      }
    },
    submitUpload() {
      if (this.$refs.upload.uploadFiles.length === 0) {
        this.$message.warning("Por favor seleccione un archivo primero.");
        return;
      }
      this.$refs.upload.submit();
    },
    onSuccess(response, file, fileList) {
      if (response.success) {
        this.$message.success(response.message);
        this.$eventHub.$emit("reloadData");
        this.$eventHub.$emit("reloadTables");
        this.close();
      } else {
        this.$message.error(response.message);
      }
      this.$refs.upload.clearFiles();
    },
    onError(error, file, fileList) {
      let mensaje = "Error al subir el archivo.";
      if (error.response && error.response.data && error.response.data.message) {
        mensaje = error.response.data.message;
      } else if (error.message) {
        mensaje = error.message;
      }
      this.$message.error(mensaje);
    },
    close() {
      this.$emit("update:showDialog", false);
    },
  },
};
</script>
